# DiscordPHP Bot Template

An unofficial way to structure a discordPHP bot.

# Table of Contents

- [Installation](#installation)
- [Important Resources](#important-resources)
- [Configuration](#configuration)
- [Message Commands](#message-commands)
  * [Creating a Message Command](#creating-a-message-command)
  * [How it works and how to use it](#how-it-works-and-how-to-use-it)
  * [Attaching Message-Based Commands](#attaching-message-based-commands)
- [Slash Commands](#slash-commands)
- [Events](#events)
- [Disabling Commands and Events](#disabling-commands-and-events)
- [Extending The Template](#extending-the-template)
  * [Bootstrap Sequence](#bootstrap-sequence)

# Installation

```
composer create-project commandstring/dphp-bot
```

# Important Resources #

[DiscordPHP Class Reference](https://discord-php.github.io/DiscordPHP/guide/)

[DiscordPHP Documentation](https://discord-php.github.io/DiscordPHP/)

[DiscordPHP Discord Server](https://discord.gg/kM7wrJUYU9)
*Only ask questions relevant to DiscordPHP's own wrapper, not on how to use this.*

[Developer Hub](https://discord.gg/TgrcSkuDtQ) *Issues about this template can be asked here*

# Configuration

Copy the `.env.example` file to `.env` and add your bot token.

Certainly! Below is an improved version of your documentation with clearer explanations and formatting:

# Message Commands
Before using message-based commands, it's important to understand the hierarchy and how they work. For more details, refer to [How it works and how to use it](#how-it-works-and-how-to-use-it).

## Creating a Message Command
To create a message-based command, create a class and attach the Core\Commands\MessageCommand attribute to it. Here's an example with a Ping command:

```php
<?php

namespace Commands\Message;

use Core\Commands\DynamicCommand;
use Discord\Parts\Channel\Message;
use Core\Commands\MessageCommand;

#[MessageCommand]
class Ping extends DynamicCommand
{
    public function handle(Message $message)
    {
        $message->reply('Pong!');
    }

    public function sendPing(Message $message)
    {
        $message->reply('**PONG!**');
    }
}
```

## How it works and how to use it
Within a Discord channel, when you send a message in the format 
```
<prefix_bot>name_command
```
it redirects to the default method of the command class. 

If you specify a subcommand with 
```
<prefix_bot>name_command subcommand_name
``` 

it redirects to the specified subcommand method.

## Attaching Message-Based Commands
Inside Bootstrap/MessageCommands.php, use a MessageCommandHandler to encapsulate your message-based command class.

Methods:<br>
``setCommandName(string $commandName):`` Sets the name of the command.<br>
``setCommandClass(string $className):`` Sets the class associated with the command.<br>
``setDefaultMethod(string $methodName):`` Sets the default method of the command class.<br>
``addSubCommand(string $subCommandName, string $methodName):`` Adds a subcommand to the command.<br>
For advanced usage:

``setInstanceManager(InstanceManager $instanceManager):`` Sets the instance manager for the command.
```php
$pingCommandHandler = Core\Commands\MessageCommandHandler::new()
    ->setCommandName('ping')
    ->setCommandClass(Ping::class)
    // ->setDefaultMethod('sendPing') // <-- By default, it invokes the "handle()" method if the default method is not explicitly set.
    ->addSubCommand('custom', 'sendPing');
```
Assign the handler to the repository to process commands when the prefix is detected within the Message Event:

```php
$commandRepository = new Core\Commands\MessageCommandRepository();
$commandRepository->addHandler($pingCommandHandler);
Env::get()->messageCommandRepository = $commandRepository;
```

# Slash Commands

Create a class that implements `Core\Commands\CommandHandler` and attach the `Core\Commands\Command` attribute to it.

```php
<?php

namespace Commands\Slash;

use Core\Commands\Command;
use Core\Commands\CommandHandler;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Interaction;

use function Core\messageWithContent;

#[Command]
class Ping implements CommandHandler
{
    public function handle(Interaction $interaction): void
    {
        $interaction->respondWithMessage(messageWithContent('Ping :ping_pong:'), true);
    }

    public function autocomplete(Interaction $interaction): void
    {
    }

    public function getConfig(): CommandBuilder
    {
        return (new CommandBuilder())
            ->setName('ping')
            ->setDescription('Ping the bot');
    }
}
```

Once you start the bot, it will automatically register the command with Discord.
And if you make any changes to the config, it will update the command on the fly.

# Events

Create a class that implements any of the interfaces found inside of `Core\Events`.
Implement the interface that matches your desired event.

```php
<?php

namespace Events;

use Core\Events\Init;
use Discord\Discord;

class Ready implements Init
{
    public function handle(Discord $discord): void
    {
        echo "Bot is ready!\n";
    }
}
```

If the interface doesn't exist use the [Class Reference](https://discord-php.github.io/DiscordPHP/guide/events/index.html). Just create a interface that has a handle methods with args that match up with the ones in the event. Then sit it inside `/Core/Events`

# Disabling Commands and Events

If you want to disable a command handler or event listener attach the `Core\Commands\Disabled` attribute to it.

```php
<?php

namespace Events;

use Core\Events\Init;
use Discord\Discord;

#[Disabled]
class Ready implements Init
{
    public function handle(Discord $discord): void
    {
        echo "Bot is ready!\n";
    }
}
```

# Extending The Template

## Bootstrap Sequence

Create a file inside `/Bootstrap` and then require it inside of `/Boostrap/Requires.php`.

## Environment Variables

Add a doc comment to `/Core/Env.php` and then add the variable to `.env`

*You should also add it to `.env.example`*
