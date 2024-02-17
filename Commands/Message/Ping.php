<?php

namespace Commands\Message;

use Core\Commands\CommandConfig;
use Discord\Parts\Channel\Message;
use Core\Commands\MessageCommand;
use Core\Commands\MessageCommandHandler;

#[MessageCommand]
class Ping implements MessageCommandHandler
{
    public function handle(Message $message): void
    {

    }

    public function getConfig(): CommandConfig
    {
        return (new CommandConfig('ping', [
            'description' => 'Send ping message',
            'aliases' => ['huh', 'bruh'],
            'title' => 'Ping Command',
        ]))->addSubCommand('bruh', 'non exist method', [
        ]);
    }
}
