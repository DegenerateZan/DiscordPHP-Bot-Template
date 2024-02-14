<?php

namespace Core\Manager;

use Core\Commands\DynamicCommand;
use Discord\Builders\MessageBuilder;
use React\EventLoop\LoopInterface;

use function Core\debug;
use function Core\discord;
use function React\Async\await;

/**
 * CommandExpirationManager is responsible for cleaning the instance of expired commands
 */
class CommandExpirationManager
{
    /**
     * commandInstances
     *
     * @var DynamicCommand[]
     */
    private $commandInstances = [];

    public function __construct(LoopInterface $eventLoop, float $cleanupIntervalInMinutes = 30.0)
    {
        $eventLoop->addPeriodicTimer($cleanupIntervalInMinutes * 60, function () {
            $this->cleanupCommands();
        });

    }

    public function addCommand(DynamicCommand $command): void
    {
        $this->commandInstances[] = $command;
    }

    public function cleanupCommands(): void
    {
        foreach ($this->commandInstances as $key => $command) {
            if ($command->isExpired()) {
                debug("Begin to free expired instance of command ['" . $command::class . "']");
                unset($this->commandInstances[$key]);
                unset($command);
            }
        }
    }
}
