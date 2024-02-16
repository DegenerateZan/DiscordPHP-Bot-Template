<?php

use Core\Commands\CommandClient;
use Core\Commands\MessageCommand;
use Core\Disabled;
use Core\Env;

use function Core\discord;
use function Core\doesClassHaveAttribute;
use function Core\env;
use function Core\loopClasses;

$mCommandClient = new CommandClient(env()->prefixManager);
$discord = discord();

loopClasses(BOT_ROOT . '/Commands/Message', static function (string $className) use ($mCommandClient) {
    /** @var T|false */
    $attribute = doesClassHaveAttribute($className, MessageCommand::class);
    $disabled = doesClassHaveAttribute($className, Disabled::class);

    if (!$attribute || $disabled !== false) {
        return;
    }
    $mCommandClient->registerCommand(new $className());
});

$mCommandClient->buildHelpCommand();

Env::get()->commandClient = $mCommandClient;
