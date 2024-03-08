<?php

namespace Core\Commands;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Exception;
use LogicException;

use function Core\codeblockify;
use function Core\discord;
use function Core\emptyValue;
use function Core\env;

class CommandClient
{
    /**
     * An array of options passed to the client.
     *
     * @var array Options.
     */
    public $commandClientOptions;

    /**
     * A map of the commands.
     *
     * @var array Commands.
     */
    public $commands = [];

    /**
     * A map of aliases for commands.
     * [
     *  "allias" => "commandName"
     * ]
     *
     * @var array Aliases.
     */
    public $aliases = [];

    private CommandPrefix $commandPrefix;

    public function __construct(CommandPrefix $commandPrefix, array $options = [])
    {
        $this->commandClientOptions = $this->resolveCommandClientOptions($options);
        $this->commandPrefix = $commandPrefix;

    }

    public function buildDefaultHelpCommand()
    {
        if ($this->commandClientOptions['defaultHelpCommand']) {
            if (!is_null($this->getCommand('help'))) {
                $this->unregisterCommand('help');
            }
            $this->registerCommand(new class () implements MessageCommandHandler {
                private $indentUsage = true;

                public function handle(Message $message): void
                {

                    $content = env()->commandClient->checkForPrefix($message);
                    $args = explode(' ', $content, 5);
                    $prefix = env()->prefixManager->getPrefix($message->guild_id);

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
                            $newCommand = env()->commandClient->getCommand($commandString);

                            if (is_null($newCommand)) {
                                $embedContent = "The command {$commandString} does not exist.";

                                continue;
                                // if the command showHelp is disabled
                            } elseif (count($newCommand->getConfig()->getHelp()) == 0) {
                                $embedContent = "The help command for {$commandString} is not available.";

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

                        $stringFormatAmount = substr_count($help['usage'], '%s');

                        if ($stringFormatAmount == 2) {
                            // Replace two occurrences of %s with command prefix and <subCommand>
                            $help['usage'] = sprintf($help['usage'], env()->prefixManager->getPrefix($message->guild_id), '<subCommand>');
                        } elseif ($stringFormatAmount == 1) {
                            // Replace one occurrence of %s with only the command prefix
                            $help['usage'] = sprintf($help['usage'], env()->prefixManager->getPrefix($message->guild_id));
                        } elseif ($stringFormatAmount > 2) {
                            // Replace the first two occurrences of %s with command prefix and <subCommand>,
                            // and fill the remaining occurrences with empty strings
                            $prefixAndSubCommand = array_merge([env()->prefixManager->getPrefix($message->guild_id), '<subCommand>'], array_fill(0, $stringFormatAmount - 2, ''));
                            $help['usage'] = vsprintf($help['usage'], $prefixAndSubCommand);
                        }
                        // If there are no %s, which means it has a custom usage

                        $usage = 'Syntax: ' . $help['usage'];

                        if (! empty(env()->commandClient->aliases) || (array_key_exists('aliases', $help))) {
                            $aliases = [];
                            foreach (env()->commandClient->aliases as $alias => $command) {
                                if ($command != $commandString) {
                                    continue;
                                }

                                $aliases[] = "{$prefix}{$alias}";

                            }
                            if (count($help['aliases']) != 0) {
                                foreach ($help['aliases'] as $alias) {
                                    $aliases[] = "{$prefix}{$alias}";
                                }
                            }

                            $concatedAliases = [];
                            foreach (array_merge(env()->commandClient->aliases, $help['aliases']) as $alias) {
                                $concatedAliases[] = $prefix . $alias;
                            }

                            if (!empty($concatedAliases)) {

                                $usage .= "\r\n" . \Core\ngettext('Alias : ', 'Aliases : ', count($concatedAliases));
                                $usage .= implode(', ', $concatedAliases);

                            }

                        }
                        $usage = (!empty($help['detailUsage'])) ? $help['detailUsage'] : $help['usage'];
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

                    if (!empty(env()->commandClient->commands)) {
                        $embed->setDescription('List Commands');
                    }

                    $longestStringAmongAll = 0;
                    $tempFieldValues = [];
                    $embedfields = [];

                    /** @var MessageCommandHandler $command */
                    foreach (env()->commandClient->commands as $command) {
                        $help = $command->getConfig()->getHelp();
                        if ($this->indentUsage) {
                            $longestStringAmongAll = max($longestStringAmongAll, strlen($command->getConfig()->commandName));
                        }

                        $allSubCommandHelp = $command->getConfig()->getAllSubCommandHelp();
                        if (empty($help['usage'])) {
                            continue;
                        }

                        // Parse string format of base command usage
                        $stringFormatAmount = substr_count($help['usage'], '%s');
                        $usage = ($stringFormatAmount > 1) ? sprintf(str_replace('%s', '', $help['usage'])) : sprintf($help['usage'], '');

                        $allSubCommandsHelp = [];
                        $allSubCommandsUsage = [];

                        foreach ($allSubCommandHelp as $key => $subCommandHelp) {
                            $allSubCommandsHelp[$key] = [
                                'usage' => sprintf($subCommandHelp['usage'], ''),
                                'description' => $subCommandHelp['description'],
                            ];
                            $allSubCommandsUsage[] = sprintf($subCommandHelp['usage'], '');
                        }

                        if ($this->indentUsage) {
                            $longestString = (empty($allSubCommandsUsage)) ? 0 : max(array_map('strlen', $allSubCommandsUsage));
                            $longestStringAmongAll = max($longestStringAmongAll, $longestString);
                        }

                        $temp = [
                            'name' => $help['name'],
                            'usage' => $usage,
                            'description' => $help['description'],
                            'subCommands' => $allSubCommandsHelp,
                        ];

                        $tempFieldValues[] = $temp;
                    }

                    foreach ($tempFieldValues as $tempFieldValue) {
                        $name = $tempFieldValue['name'];
                        $usage = $tempFieldValue['usage'];
                        $len = $longestStringAmongAll - strlen($usage);
                        $usage = $usage . str_repeat(' ', ($len < 0) ? 0 : $len);

                        $fieldValues = ["``{$usage} :``" . $tempFieldValue['description']];

                        foreach ($tempFieldValue['subCommands'] as $subCommand) {
                            $subCommandUsage = $subCommand['usage'];
                            $len = $longestStringAmongAll - strlen($subCommandUsage);
                            $subCommandUsage = $subCommandUsage . str_repeat(' ', ($len < 0) ? 0 : $len);

                            $fieldValues[] = '``' . $subCommandUsage . ' :``' . $subCommand['description'];
                        }

                        $embedfields[] = [
                            'name' => "__**{$name}**__",
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

                }

                public function getConfig(): CommandConfig
                {
                    return (new CommandConfig())
                        ->setName('help')
                        ->setTitle('Help Command')
                        ->setDescription('Provides a list of commands available.')
                        ->setUsage('%s%s');
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
     * @throws Exception
     *
     * @return Command    The command instance.
     */
    public function registerCommand(MessageCommandHandler $command): void
    {
        $this->validateCommand($command);
        $commandName = $command->getConfig()->commandName;
        if (null !== $commandName && $this->commandClientOptions['caseInsensitiveCommands']) {
            $commandName = strtolower($commandName);
        }
        if (array_key_exists($commandName, $this->commands)) {
            throw new Exception("A command with the name {$commandName} already exists, attempted to assign class " . $command::class . '.');
        }
        $this->commands[$commandName] = $command;
    }

    private function validateCommand(MessageCommandHandler $command): void
    {
        $config = $command->getConfig();
        $config->validate();

        if (!method_exists($command, $config->defaultMethod)) {
            throw new LogicException("Target default method '{$config->defaultMethod}' for command class '" . get_class($command) . "' does not exist");
        }

        if (!is_callable([$command, $config->defaultMethod])) {
            throw new LogicException("Target default method '{$config->defaultMethod}' for command class '" . get_class($command) . "' is not accessible, please use a public access modifier instead");
        }

        foreach ($config->subCommands as $subCommand) {
            $subCommand->validate();

            if (!method_exists($command, $subCommand->method)) {
                throw new LogicException("Target method '{$subCommand->method}' for command class '" . get_class($command) . "' does not exist");
            }

            if (!is_callable([$command, $subCommand->method])) {
                throw new LogicException("Target method '{$subCommand->method}' for command class '" . get_class($command) . "' is not accessible, please use a public access modifier instead");
            }
        }
    }

    /**
     * Unregisters a command.
     *
     * @param  string     $command The command name.
     *
     * @throws Exception
     */
    public function unregisterCommand(string $command): void
    {
        if (! array_key_exists($command, $this->commands)) {
            throw new Exception("A command with the name {$command} does not exist.");
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
        if (array_key_exists($alias, $this->aliases)) {
            throw new LogicException("A command alias with the name {$alias} already exist.");
        }
        $this->aliases[$alias] = $command;
    }

    /**
     * Unregisters a command alias.
     *
     * @param  string     $alias The alias name.
     *
     * @throws Exception
     */
    public function unregisterCommandAlias(string $alias): void
    {
        if (! array_key_exists($alias, $this->aliases)) {
            throw new Exception("A command alias with the name {$alias} does not exist.");
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
                'description',
                'defaultHelpCommand',
                'caseInsensitiveCommands',
            ])
            ->setDefaults([
                'defaultHelpCommand' => true,
                'description' => 'A bot made with DiscordPHP ' . Discord::VERSION . '.',
                'caseInsensitiveCommands' => false,
            ]);

        $options = $resolver->resolve($options);

        return $options;
    }
}
