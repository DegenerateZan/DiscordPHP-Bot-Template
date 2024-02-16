<?php

namespace Core\Commands;

use Discord\Parts\Channel\Message;

interface MessageCommandHandler
{
    public function handle(Message $message): void;

    public function getConfig(): CommandConfig;
}
