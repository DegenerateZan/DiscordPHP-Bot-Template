<?php

namespace Commands\Message;

use Core\Commands\CommandConfig;
use Core\Commands\DynamicCommand;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Core\Commands\MessageCommand;
use Core\Commands\MessageCommandHandler;

#[MessageCommand]
class Test extends DynamicCommand implements MessageCommandHandler
{
    public function handle(Message $message): void
    {
        $button = Button::new(Button::STYLE_PRIMARY)->setLabel('Bruh');

        $actionRow = new ActionRow();
        $actionRow->addComponent($button);

        $builder = new MessageBuilder();
        $builder->addComponent($actionRow);

        $message->reply($builder);
    }

    private function sendMessage(Interaction $interaction)
    {
        $interaction->respondWithMessage(MessageBuilder::new()->setContent('Button has pressed'));
    }

    public function getConfig(): CommandConfig
    {
        return new CommandConfig('test', [
            'showHelp' => false,
        ]);
    }
}
