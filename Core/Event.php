<?php

namespace Core;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Event
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
