<?php

use Commands\Message\Ping;
use Commands\Message\Test;
use Core\Commands\MessageCommandHandler;
use Core\Env;

$pingCommand = MessageCommandHandler::new()
    ->setCommandName('ping')
    ->setCommandClass(Ping::class)
    ->setDefaultMethod('sendPing');

$testCommand = MessageCommandHandler::new()
    ->setCommandName('test')
    ->setCommandClass(Test::class);

$commandRepository = new Core\Commands\MessageCommandRepository();

$commandRepository->addHandler($pingCommand);
$commandRepository->addHandler($testCommand);

Env::get()->messageCommandRepository = $commandRepository;
