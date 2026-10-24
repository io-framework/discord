<?php

namespace Io\Discord\Attributes;

use Attribute;

/**
 * Handles sent modals with this id, or with an id that starts with "$id:". A string parameter gets the field of
 * its name, a parameter of type Submit the whole answer. What the method returns is the reply.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ModalSubmit
{
    public function __construct(
        public string $id,
    ) {}
}
