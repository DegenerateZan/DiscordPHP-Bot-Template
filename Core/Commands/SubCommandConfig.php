<?php

namespace Core\Commands;

use InvalidArgumentException;

/**
 * Designed to set Configuration of the SubCommand by encapsulating information
 */
class SubCommandConfig
{
    /**
     * @var string The name of the command.
     */
    public string $name;

    /**
     * @var string The target method associated with the Sub Command.
     */
    public string $method;

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
     * @var bool Indicates whether to show help information of the SubCommand.
     */
    public bool $showHelp;

    public function __construct()
    {
        $this->description = 'No description provided.';
        $this->longDescription = '';
        $this->usage = '%s%s %s';
        $this->showHelp = true;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

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

    public function validate(): void
    {
        if (empty($this->name)) {
            throw new InvalidArgumentException('Name of the command cannot be empty');
        }

        if (empty($this->method)) {
            throw new InvalidArgumentException('Method of the command cannot be empty');
        }

        if (empty($this->description)) {
            throw new InvalidArgumentException('Description of the command cannot be empty');
        }

        if (empty($this->usage)) {
            throw new InvalidArgumentException('Usage of the command cannot be empty');
        }

    }
}
