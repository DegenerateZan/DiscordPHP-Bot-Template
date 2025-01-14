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
To create a message-based command, create a class that implements `Core\Commands\CommandHandler` and attach the Core\Commands\MessageCommand attribute to it.

```php
<?php

namespace Commands\Message;

use Core\Commands\CommandConfig;
use Core\Commands\DynamicCommand;
use Discord\Parts\Channel\Message;
use Core\Commands\MessageCommand;
use Core\Commands\MessageCommandHandler;

#[MessageCommand]
class Ping extends DynamicCommand implements MessageCommandHandler
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
```

Once you start the bot, it will automatically register the command with Discord.
And if you make any changes to the config, it will update the command on the fly.


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

# Hot Reloading

This template has a built-in HMR (Hot Module Reloading) system.
Which essentially means that while you're developing your
bot.

The code will automatically be updated without having to restart the bot.
Set HMR in your `.env` file to `true` to enable it. 

**Note: HMR only works on Commands and Events. (~~for now~~)**

