<?php

namespace Core\Commands;

use Closure;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;

use function Core\codeblockify;
use function Core\discord;
use function Core\emptyValue;

class CommandClient
{
    /**
     * An array of options passed to the client.
     *
     * @var array Options.
     */
    protected $commandClientOptions;

    /**
     * A map of the commands.
     *
     * @var array Commands.
     */
    protected $commands = [];

    /**
     * A map of aliases for commands.
     * [
     *  "allias" => "commandName"
     * ]
     *
     * @var array Aliases.
     */
    protected $aliases = [];

    private CommandPrefix $commandPrefix;

    public function __construct(CommandPrefix $commandPrefix, array $options = [])
    {
        $this->commandClientOptions = $this->resolveCommandClientOptions($options);
        $this->commandPrefix = $commandPrefix;

    }

    public function buildHelpCommand()
    {

        if (!is_null($this->getCommand('help'))) {
            $this->unregisterCommand('help');
        }

        $handleCallback = function (Message $message): void {
            $content = $this->checkForPrefix($message);
            $args = explode(' ', $content, 5);
            $prefix = $this->commandPrefix->getPrefix($message->guild_id);

            $commandConfig = null;
            $embedContent = '';
            $help = null;
            $subCommandString = null;

            if (count($args) > 1) {
                unset($args[0]);
                $args = array_merge($args);
                while (count($args) > 0) {
                    $commandString = array_shift($args);
                    if (!empty($args)) {
                        $subCommandString = implode(' ', $args);
                    }
                    $newCommand = $this->getCommand($commandString);

                    if (is_null($newCommand)) {
                        $embedContent = "The command {$commandString} does not exist.";

                        continue;
                    }
                    $commandConfig = $newCommand->getConfig();
                    $help = $commandConfig->getHelp();
                    if (!is_null($newCommand) && !is_null($subCommandString)) {
                        if (!is_null($commandConfig->doesSubCommandExist($subCommandString))) {
                            $help = $commandConfig->getSubCommandHelp($subCommandString);
                        }

                        continue;
                    }

                }

                $embed = new Embed(discord());

                if (empty($help)) {
                    $embed->setDescription($embedContent);
                    $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));

                    return;
                }

                $help['usage'] = sprintf($help['usage'], $this->commandPrefix->getPrefix($message->guild_id));

                $usage = 'Syntax: ' . $help['usage'];

                if (! empty($this->aliases) || (array_key_exists('aliases', $help))) {
                    $aliases = [];
                    foreach ($this->aliases as $alias => $command) {
                        if ($command != $commandString) {
                            continue;
                        }

                        $aliases[] = "{$prefix}{$alias}";

                    }
                    if (!empty($help['aliases'])) {
                        foreach ($help['aliases'] as $alias) {
                            $aliases[] = "{$prefix}{$alias}";
                        }
                    }

                    $concatedAliases = [];
                    foreach (array_merge($this->aliases, $help['aliases']) as $alias) {
                        $concatedAliases[] = $prefix . $alias;
                    }

                    $usage .= "\r\n" . \ngettext('Alias : ', 'Aliases : ', count($concatedAliases));
                    $usage .= implode(', ', $concatedAliases);
                }
                $usage = codeblockify('ansi', $usage);
                $description = (!empty($help['longDescription'])) ? $help['longDescription'] : $help['description'];

                $embed->setAuthor(discord()->username . ' Help Menu', discord()->avatar)
                    ->setDescription($usage)
                    ->addFieldValues(emptyValue(), $description)
                    ->setTimestamp()
                    ->setFooter(discord()->username);

                if (! empty($help['subCommandsHelp'])) {
                    foreach ($help['subCommandsHelp'] as $subCommandHelp) {
                        $embed->addFieldValues($subCommandHelp['command'], $subCommandHelp['description'], true);
                    }
                }

                $message->channel->sendEmbed($embed);

                return;
            }

            // This when it shows all help commands
            // Help Command help, lol

            $embed = new Embed(discord());

            $embed->setAuthor(discord()->username . ' Help Menu', discord()->avatar)
                ->setDescription('Page 1 of 1')
                ->setTimestamp()
                ->setFooter(discord()->username);

            if (!empty($this->commands)) {
                $embed->setDescription('List Commands');
            }

            $commandsDescription = '';
            $embedfields = [];
            /** @var MessageCommandHandler $command */
            foreach ($this->commands as $command) {
                $help = $command->getConfig()->getHelp();
                $allSubCommandHelp = $command->getConfig()->getAllSubCommandHelp();
                if (empty($help)) {
                    continue;
                }

                $fieldValues = [];

                $fieldValues[] = codeblockify('', sprintf($help['usage'] . "\t:", ''), 1, true) . $help['description'];

                foreach ($allSubCommandHelp as $subCommandHelp) {
                    $fieldValues[] = codeblockify('', sprintf($subCommandHelp['usage'] . "\t:", ''), 1, true) . $subCommandHelp['description'];

                }

                $embedfields[] = [
                    'name' => "__**{$help['name']}**__",
                    'value' => implode("\n", $fieldValues),
                    'inline' => false,
                ];
            }
            // Use embed fields in case commands count is below limit
            if (count($embedfields) <= 25) {
                foreach ($embedfields as $field) {
                    $embed->addField($field);
                }
                $commandsDescription = '';
            }

            $message->channel->sendEmbed($embed);

        };

        if ($this->commandClientOptions['defaultHelpCommand']) {
            $this->registerCommand(new class ($handleCallback) implements MessageCommandHandler {
                public function __construct(
                    private Closure $handleCallback
                ) {
                }

                public function handle(Message $message): void
                {
                    $callable = $this->handleCallback;
                    $callable($message);
                }

                public function getConfig(): CommandConfig
                {
                    return new CommandConfig('help', [
                        'description' => 'Provides a list of commands available.',
                        'usage' => '%s%s',
                        'title' => 'Help Command',
                    ]);
                }
            });
        }
    }

    /**
     * Checks for a prefix in the message content, and returns the content of
     * the message based on different guild, minus the prefix if a prefix was detected. If no prefix is
     * detected, null is returned.
     */
    public function checkForPrefix(Message $message): ?string
    {
        $guildId = $message->channel->guild_id;
        $prefix = $this->commandPrefix->getPrefix($guildId);

        if (strpos($message->content, $prefix) !== 0) {
            return null;
        }

        return substr($message->content, strlen($prefix));
    }

    /**
     * Registers a new command.
     *
     * @throws \Exception
     *
     * @return Command    The command instance.
     */
    public function registerCommand(MessageCommandHandler $command): void
    {
        $commandName = $command->getConfig()->getCommandName();
        if (null !== $commandName && $this->commandClientOptions['caseInsensitiveCommands']) {
            $commandName = strtolower($commandName);
        }
        if (array_key_exists($commandName, $this->commands)) {
            throw new \Exception("A command with the name {$commandName} already exists, attempting to assign class " . $command::class . '.');
        }
        $this->commands[$commandName] = $command;
    }

    /**
     * Unregisters a command.
     *
     * @param  string     $command The command name.
     *
     * @throws \Exception
     */
    public function unregisterCommand(string $command): void
    {
        if (! array_key_exists($command, $this->commands)) {
            throw new \Exception("A command with the name {$command} does not exist.");
        }

        unset($this->commands[$command]);
    }

    /**
     * Registers a command alias.
     *
     * @param string $alias   The alias to add.
     * @param string $command The command.
     */
    public function registerAlias(string $alias, string $command): void
    {
        $this->aliases[$alias] = $command;
    }

    /**
     * Unregisters a command alias.
     *
     * @param  string     $alias The alias name.
     *
     * @throws \Exception
     */
    public function unregisterCommandAlias(string $alias): void
    {
        if (! array_key_exists($alias, $this->aliases)) {
            throw new \Exception("A command alias with the name {$alias} does not exist.");
        }

        unset($this->aliases[$alias]);
    }

    /**
     * Attempts to get a command, by the
     *
     * @param string $command The command to get.
     * @param bool   $aliases Whether to search aliases as well.
     *
     * @return MessageCommandHandler|null The command.
     */
    public function getCommand(string $command, bool $aliases = true): ?MessageCommandHandler
    {
        if (array_key_exists($command, $this->commands)) {
            return $this->commands[$command];
        }

        if (array_key_exists($command, $this->aliases) && $aliases) {
            return $this->commands[$this->aliases[$command]];
        }

        return null;
    }

    /**
     * Resolves the options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveCommandClientOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'name',
                'description',
                'defaultHelpCommand',
                'caseInsensitiveCommands',
            ])
            ->setDefaults([
                'name' => '<UsernamePlaceholder>',
                'defaultHelpCommand' => true,
                'description' => 'A bot made with DiscordPHP ' . Discord::VERSION . '.',
                'caseInsensitiveCommands' => false,
            ]);

        $options = $resolver->resolve($options);

        return $options;
    }
}
