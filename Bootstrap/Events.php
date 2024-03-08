<?php

use Core\Disabled;
use Core\Events\Event;

use function Core\debug;
use function Core\discord;
use function Core\doesClassHaveAttribute;
use function Core\loopClasses;

$events = [];
$discord = discord();

loopClasses(BOT_ROOT . '/Core/Events', static function (string $className) use (&$events) {
    if (!interface_exists($className) || $className === Event::class) {
        return;
    }

    /** @var T|false */
    $attribute = doesClassHaveAttribute($className, Event::class);

    if (!$attribute) {
        return;
    }

    $events[$className] = $attribute->newInstance()->name;
});

loopClasses(BOT_ROOT . '/Events', static function (string $className) use ($events, $discord) {
    if (doesClassHaveAttribute($className, Disabled::class) !== false) {
        return;
    }

    $event = new $className();
    $reflection = new ReflectionClass($event);

    foreach ($reflection->getInterfaceNames() as $interface) {
        $eventName = $events['\\' . $interface] ?? null;

        if ($eventName === null) {
            continue;
        }
        debug('Begin adding Event from class ' . $event::class);
        $discord->on($eventName, $event->handle(...));
    }
});
