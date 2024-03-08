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
        $message->reply('Pong');
    }

    public function getConfig(): CommandConfig
    {
        return (new CommandConfig())
            ->setName('ping')
            ->setDescription('Send ping message')
            ->setAliases(['p'])
            ->setTitle('Ping Command');
    }
}
