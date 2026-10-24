<?php

namespace Io\Discord\Attributes;

use Attribute;

/**
 * Handles clicks on buttons with this id, or with an id that starts with "$id:". A parameter of type Click gets
 * the click. What the method returns (a string or a Message) is the reply.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ButtonClick
{
    public function __construct(
        public string $id,
    ) {}
}
