<?php

namespace Core\Commands;

use Core\HMR\InstanceManager;
use LogicException;

/**
 * Class MessageCommandRepository
 *
 * Manages message command handlers by providing methods to add, retrieve, remove, and validate handlers.
 */
class MessageCommandRepository
{
    /** @var MessageCommandHandler[] */
    private array $handlers = [];

    /**
     * Adds a MessageCommandHandler to the repository.
     *
     * @param MessageCommandHandler $handler The MessageCommandHandler instance to be added.
     *
     * @throws LogicException If the provided MessageCommandHandler fails validation.
     */
    public function addHandler(MessageCommandHandler $handler): void
    {
        $handler->validate();
        $this->handlers[$handler->getCommandName()] = $handler;
    }

    /**
     * Retrieves the Command mapping for the specified command and optional subcommand.
     *
     * @param string $fullCommands The full command string (excluding any prefix) containing the command and optional subcommand.
     *
     * @return object|null An object representing the Command mapping, or null if the specified command or subcommand is not found.
     *                      The object structure includes the Command instance and method to be executed.
     *                      - instance: An instance of the Command class created using the createInstance method.
     *                      - method: The method to be executed, either the specified subcommand method or the default method
     *                        if no subcommand is provided or if the specified subcommand is not found.
     */
    public function getCommandMapping(string $fullCommands): ?object
    {
        [$commandName, $subCommand] = array_pad(explode(' ', $fullCommands, 3), 2, null);

        if ($this->hasHandler($commandName)) {
            $handler = $this->handlers[$commandName];

            return (object) [
                'instance' => $handler->createInstance(),
                'method' => $this->resolveMethod($handler, $subCommand),
            ];
        }

        return null;
    }

    /**
     * Resolves the method to execute for the command handler.
     *
     * @param MessageCommandHandler $handler    The message command handler.
     * @param string|null           $subCommand The optional subcommand.
     *
     * @return string The method name.
     */
    private function resolveMethod(MessageCommandHandler $handler, ?string $subCommand): string
    {
        return $subCommand && ($method = $handler->getSubCommand($subCommand))
            ? $method
            : $handler->getDefaultMethod();
    }

    /**
     * Retrieves the handler instance by name.
     *
     * @param string $commandName The name of the command handler.
     *
     * @return MessageCommandHandler The command handler instance.
     */
    public function getHandler(string $commandName): MessageCommandHandler
    {
        return $this->handlers[$commandName];
    }

    /**
     * Retrieves the instance manager associated with the command handler by name.
     *
     * @param string $commandName The name of the command handler.
     *
     * @return InstanceManager|null The instance manager or null if the specified handler is not found.
     */
    public function getInstanceManager(string $commandName): ?InstanceManager
    {
        return $this->handlers[$commandName]->getInstanceManager();
    }

    /**
     * Checks if a command handler exists.
     *
     * @param string $commandName The name of the command handler to check for existence.
     *
     * @return bool If the provided command handler name exists.
     */
    public function hasHandler(string $commandName): bool
    {
        return array_key_exists($commandName, $this->handlers);
    }

    /**
     * Checks if a subcommand exists for the specified command handler.
     *
     * @param string      $commandName      The name of the command handler.
     * @param string|null $subCommandName   The name of the subcommand to check for existence (optional).
     *
     * @return bool True if the command handler exists. If $subCommandName is provided, returns false
     *              if the subcommand does not exist for the specified command handler.
     */
    public function doesSubCommandExist(string $commandName, string $subCommandName = null): bool
    {
        return $this->hasHandler($commandName)
            && ($subCommandName === null || $this->handlers[$commandName]->getSubCommand($subCommandName) !== null);
    }

    /**
     * Removes a command handler by name.
     *
     * @param string $commandName The name of the command handler to remove.
     *
     * @return bool True if the command handler was removed, false if the command handler was not found.
     */
    public function removeHandler(string $commandName): bool
    {
        if (isset($this->handlers[$commandName])) {
            unset($this->handlers[$commandName]);

            return true;
        }

        return false;
    }
}
