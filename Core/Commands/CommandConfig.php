<?php

namespace Core\Commands;

use InvalidArgumentException;
use LogicException;

/**
 * Designed to set Configuration of the Command, providing a structured way to define and handle different commands by encapsulating information
 * such as command name, command class, subcommands, and default methods.
 */
class CommandConfig
{
    /**
     * @var string The name of the command.
     */
    public string $commandName;

    /**
     * @var string The default method to be executed (default is 'handle').
     */
    public string $defaultMethod = 'handle';

    /**
     * @var string The title of the command.
     */
    public string $title;

    /**
     * @var string A brief description of the command.
     */
    public string $description;

    /**
     * @var string A detailed description of the command.
     */
    public string $longDescription;

    /**
     * @var string The basic usage syntax of the command.
     */
    public string $usage;

    /**
     * @var string Additional details on command usage.
     */
    public string $detailUsage;

    /**
     * @var array An array of command aliases.
     */
    public array $aliases = [];

    /**
     * @var int The cooldown time for the command.
     */
    public int $cooldown;

    /**
     * @var string The message to display during the cooldown period.
     */
    public string $cooldownMessage;

    /**
     * @var string The help information for the command.
     */
    public string $showHelp;

    /**
     * @var array Associative array of subcommands and their associated method names.
     */
    public array $subCommands = [];

    /**
     * Static factory method to create a new instance of CommandConfig.
     */
    public static function new(): self
    {
        return new static();
    }

    public function __construct()
    {
        $this->description = 'No description provided.';
        $this->longDescription = '';
        $this->usage = '%s%s %s';
        $this->showHelp = true;
    }

    /**
     * Add a subcommand with its associated method name and options.
     *
     *
     * @return $this
     */
    public function addSubCommand(SubCommandConfig $subCommandConfig): self
    {
        $name = $subCommandConfig->name;
        if (array_key_exists($name, $this->subCommands)) {
            throw new LogicException("Attempt to assigned a sub command [{$name}] that has a same name");
        }
        $this->subCommands[$subCommandConfig->name] = $subCommandConfig;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->commandName = $name;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setAliases(array $aliases): self
    {
        $this->aliases = $aliases;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setLongDescription(string $longDescription): self
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    /**
     * Note: This is intended for display in help usage, not in the actual command invocation.
     * You can provide additional detailed usage here. For custom usage, follow the format '%s%s %s' '%s' string format,
     * where the 1st placeholder is the prefix, the 2nd is the command name, and the 3rd is the subcommand name.
     * This is optional; you can add content without placeholders, but automatic values won't be generated based on the command call,
     * so you'll need to set it manually.
     */
    public function setUsage(string $usage): self
    {
        $this->usage = $usage;

        return $this;
    }

    /**
     * Note: This is intended for display in help usage, not in the actual command invocation.
     * You can provide additional detailed usage here. For custom usage, follow the format '%s%s %s' '%s' string format,
     * where the 1st placeholder is the prefix, the 2nd is the command name, and the 3rd is the subcommand name.
     * This is optional; you can add content without placeholders, but automatic values won't be generated based on the command call,
     * so you'll need to set it manually.
     */
    public function setDetailUsage(string $detailUsage): self
    {
        $this->detailUsage = $detailUsage;

        return $this;
    }

    public function setShowHelp(bool $showHelp): self
    {
        $this->showHelp = $showHelp;

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

    /**
     * Get the method name and options associated with a specific subcommand.
     */
    public function getSubCommand(string $subCommandName): ?SubCommandConfig
    {
        return $this->subCommands[$subCommandName] ?? null;
    }

    public function doesSubCommandExist(string $subCommandName): bool
    {
        return array_key_exists($subCommandName, $this->subCommands);
    }

    public function validate(): void
    {
        if (empty($this->commandName)) {
            throw new InvalidArgumentException('Command name cannot be empty');
        }

        if (empty($this->title)) {
            throw new InvalidArgumentException('Command title name cannot be empty');
        }

        if (empty($this->defaultMethod)) {
            throw new InvalidArgumentException('Method of the command cannot be empty');
        }

        if (empty($this->usage)) {
            throw new InvalidArgumentException('Usage of the command cannot be empty');
        }

    }

    public function getHelp(): array|null
    {
        if (!$this->showHelp) {
            return null;
        }

        return [
            'name' => $this->title,
            'description' => $this->description,
            'longDescription' =>  $this->longDescription,
            'usage' => sprintf($this->usage, '%s', $this->commandName, '%s'),
            'detailUsage' => sprintf($this->detailUsage, '%s', $this->commandName, '%s'),
            'aliases' => $this->aliases,

        ];
    }

    public function getSubCommandHelp($subCommandName): ?array
    {
        if (!$this->doesSubCommandExist($subCommandName) || !$this->showHelp || !$this->getSubCommand($subCommandName)->showHelp) {
            return null;
        }

        // Check whether the usage contains '%s' string format; if so, it will execute when using the default usage template.
        if (str_contains($this->usage, '%s')) {
            $usage = sprintf($this->usage, '%s', $this->commandName, $subCommandName);
        } else {
            $usage = $this->usage;
        }

        if (str_contains($this->usage, '%s')) {
            $detailUsage = sprintf($this->usage, '%s', $this->commandName, $subCommandName);
        } else {
            $detailUsage = $this->usage;
        }

        return [
            'description' => $this->description,
            'longDescription' => $this->longDescription,
            'usage' => $usage,
            'detailUsage' => $detailUsage,

        ];
    }

    public function getAllSubCommandHelp(): array
    {
        if (!$this->showHelp) {
            return null;
        }
        $helps = [];
        /** @var SubCommandConfig $value */
        foreach ($this->subCommands as $key => $value) {
            if (!$value->showHelp) {
                continue;
            }

            // check whether if the usage contained %s string format,it'll execute when its from the default usage template
            if (str_contains($this->usage, '%s')) {
                $usage = sprintf($this->usage, '%s', $this->commandName, $value->name);
            } else {
                $usage = $this->usage;
            }

            if (str_contains($this->usage, '%s')) {
                $detailUsage = sprintf($this->usage, '%s', $this->commandName, $value->name);
            } else {
                $detailUsage = $this->usage;
            }

            $helps[] = [
                'description' => $value->description,
                'longDescription' =>  $value->longDescription,
                'usage' => $usage,
                'detailUsage' => $detailUsage,
            ];
        }

        return $helps;
    }
}
