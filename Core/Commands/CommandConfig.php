<?php

namespace Core\Commands;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Designed to set Configuration of the Command, providing a structured way to define and handle different commands by encapsulating information
 * such as command name, command class, subcommands, and default methods.
 */
class CommandConfig
{
    private string $commandName;
    private array $options;
    private string $defaultMethod = 'handle';

    /**
     * @var array Associative array of subcommands and their associated method names.
     */
    private array $subCommands = [];

    /**
     * Static factory method to create a new instance of CommandConfig.
     */
    public static function new(string $commandName, array $options = []): self
    {
        return new static($commandName, $options = []);
    }

    public function __construct(string $commandName, array $options = [])
    {
        $this->commandName = $commandName;
        $this->options = $this->resolveCommandOptions($options);
    }

    /**
     * Add a subcommand with its associated method name and options.
     *
     *
     * @return $this
     */
    public function addSubCommand(string $subCommandName, string $methodName, array $options = []): self
    {
        $this->subCommands[$subCommandName] = [
            'method' => $methodName,
            'options' => $this->resolveSubCommandOptions($options),
        ];

        return $this;
    }

    /**
     * Set the default method name, as default, it'll use handle(), if you're not explicitly set it.
     *
     * @return $this
     */
    public function setDefaultMethod(string $methodName): self
    {
        $this->defaultMethod = $methodName;

        return $this;
    }

    public function getCommandName(): string
    {
        return $this->commandName;
    }

    /**
     * Get the subcommands and their associated method names.
     */
    public function getSubCommands(): array
    {
        return $this->subCommands;
    }

    /**
     * Get the method name and options associated with a specific subcommand.
     *
     *
     * @return array|null assoc array ["method" => "methodName", "config" => $subCommandConfig]
     */
    public function getSubCommand(string $subCommandName): ?array
    {
        return $this->subCommands[$subCommandName] ?? null;
    }

    /**
     * Get the default method name.
     */
    public function getDefaultMethod(): string
    {
        return $this->defaultMethod;
    }

    public function getCommandOptions(): array
    {
        return $this->options;
    }

    public function getSubCommandOptions(string $subCommandName): array|null
    {
        if (!$this->doesSubCommandExist($subCommandName)) {
            return null;
        }

        return $this->subCommands[$subCommandName]['options'];
    }

    public function doesSubCommandExist(string $subCommandName): bool
    {
        return array_key_exists($subCommandName, $this->subCommands);
    }

    /**
     * Resolves subcommand options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveSubCommandOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'description',
                'longDescription',
                'usage',
                'cooldownMessage',
                'showHelp',
            ])
            ->setDefaults([
                'description' => 'No description provided.',
                'longDescription' => '',
                'usage' => '%s%s %s',
                'cooldownMessage' => 'Please wait %d second(s) to use this command again.',
                'showHelp' => true,
            ]);
        if (empty($this->subCommands)) {
            $this->options['usage'] .= ' %s';
        }

        return $resolver->resolve($options);
    }

    /**
     * Resolves command options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveCommandOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'title',
                'description',
                'longDescription',
                'usage',
                'aliases',
                'cooldown',
                'cooldownMessage',
                'showHelp',

            ])
            ->setDefaults([
                'title' => 'Title Not Set!',
                'description' => 'No description provided.',
                'longDescription' => '',
                'usage' => '%s%s',
                'aliases' => [],
                'cooldown' => 0,
                'cooldownMessage' => 'Please wait %d second(s) to use this command again.',
                'showHelp' => true,

            ]);

        return $resolver->resolve($options);
    }

    public function getHelp(): array|null
    {
        if (!$this->options['showHelp']) {
            return [];
        }
        $options = $this->getCommandOptions();

        return [
            'name' => $options['title'],
            'description' => $options['description'],
            'longDescription' =>  $options['longDescription'],
            'usage' => sprintf($options['usage'], '%s', $this->commandName, '%s'),
            'aliases' => [],

        ];
    }

    public function getSubCommandHelp($subCommandName): array|null
    {
        if (!$this->doesSubCommandExist($subCommandName)) {
            return null;
        }

        if (!$this->options['showHelp']) {
            return [];
        }

        if (!$this->getSubCommandOptions($subCommandName)['showHelp']) {
            return [];
        }

        $options = $this->getSubCommandOptions($subCommandName);

        return [
            'description' => $options['description'],
            'longDescription' =>  $options['longDescription'],
            'usage' => sprintf($options['usage'], '%s', $this->commandName, $subCommandName),
        ];
    }

    public function getAllSubCommandHelp(): array
    {
        if (!$this->options['showHelp']) {
            return [];
        }
        $helps = [];
        foreach ($this->getSubCommands() as $key => $value) {
            $options = $value['options'];
            $helps[] = [
                'description' => $options['description'],
                'longDescription' =>  $options['longDescription'],
                'usage' => sprintf($options['usage'], '%s', $this->commandName, $key),
            ];
        }

        return $helps;
    }
}
