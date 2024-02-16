<?php

namespace Events;

use Core\Commands\CommandClient;
use Core\Commands\DynamicCommand;
use Core\Events\MessageCreate;
use Core\Manager\CommandExpirationManager;
use Core\Env;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message as ChannelMessage;
use Discord\Parts\Embed\Embed;
use LogicException;
use Throwable;

use function Core\env;
use function Core\discord as d;

class Message implements MessageCreate
{
    private CommandExpirationManager $expirationManager;
    private CommandClient $commandClient;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->expirationManager = new CommandExpirationManager(d()->getLoop(), 0.05);
        $this->commandClient = Env::get()->commandClient;
    }

    public function handle(ChannelMessage $message, Discord $discord): void
    {
        $fullCommands = $this->commandClient->checkForPrefix($message);
        if (is_null($fullCommands)) {
            return;
        }
        [$commandName, $subCommand] = array_pad(explode(' ', $fullCommands, 3), 2, null);

        try {
            $commandInstance = $this->handleCommand($message, $commandName);
        } catch (Throwable $e) {
            self::handleError($e, $message);
            unset($commandInstance);

            return;
        }

        if ($commandInstance instanceof DynamicCommand) {
            $this->expirationManager->addCommand($commandInstance);
        }
    }

    /**
     * @throws LogicException
     */
    private function handleCommand(ChannelMessage $message, string $commandName): ?DynamicCommand
    {
        $command = $this->commandClient->getCommand($commandName);
        $command->handle($message);

        return null;

        if (is_null($command)) {
            return null;
        }

        if ($command->instance instanceof DynamicCommand) {
            $command->instance->$command->method($message);

            return $command->instance;
        }

        if (method_exists($command->instance, $command->method)) {
            unset($command);

            return null;
        } else {
            $className = $command->instance::class;
            $methodName = $command->method;
            throw new LogicException("Method '$methodName' of class '$className' for command '$commandName' does not exist");
        }
    }

    /**
     * Handles and logs errors.
     */
    public static function handleError(Throwable $e, ChannelMessage $message): void
    {
        $discord = env()->discord;

        $embed = (new Embed($discord))
            ->setTitle('Exception Caught')
            ->setDescription('An exception occurred while processing your request.')
            ->setColor('#FF0000')
            ->setFooter($discord->username)
            ->setTimestamp()
            ->addField(['name' => 'Type', 'value' => get_class($e)])
            ->addField(['name' => 'Message', 'value' => $e->getMessage()])
            ->addField(['name' => 'File', 'value' => $e->getFile()])
            ->addField(['name' => 'Line', 'value' => $e->getLine()]);

        $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
    }
}
