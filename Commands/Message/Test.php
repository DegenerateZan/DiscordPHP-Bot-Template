<?php

namespace Commands\Message;

use Core\Commands\DynamicCommand;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Core\Commands\MessageCommand;

#[MessageCommand]
class Test extends DynamicCommand
{
    private Message $message;

    public function __construct()
    {
        $this->setTimeLimit(time() + 0.5);
    }

    public function handle(Message $message)
    {
        $this->message = $message;
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
}
