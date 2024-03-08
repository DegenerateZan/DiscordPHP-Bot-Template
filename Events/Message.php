<?php

namespace Events;

use Core\Commands\CommandClient;
use Core\Commands\DynamicCommand;
use Core\Events\MessageCreate;
use Core\Manager\ExpirationManager;
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
    private ExpirationManager $expirationManager;
    private CommandClient $commandClient;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->expirationManager = new ExpirationManager(d()->getLoop(), 0.05);
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
            $commandInstance = $this->handleCommand($message, $commandName, $subCommand);
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
    private function handleCommand(ChannelMessage $message, string $commandName, $subCommand): ?DynamicCommand
    {

        $command = $this->commandClient->getCommand($commandName);
        if (is_null($command)) {
            return null;
        }

        $methodName = $command->getConfig()->defaultMethod;

        $subCommandConfig = $command->getConfig()->getSubCommand($subCommand);

        // check whether if subcommand is found
        if (!is_null($subCommandConfig)) {
            $methodName = $subCommandConfig->method;
        }

        $command->$methodName($message);

        if ($command instanceof DynamicCommand) {
            return $command;
        } else {
            unset($command);

            return null;
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
