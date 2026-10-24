<?php

namespace Io\Discord\Attributes;

use Attribute;

/**
 * Describes an option of a slash command, as Discord shows it. With $suggest, a method of the same class
 * suggests values while the user types: it gets an Autocomplete and returns values or names => values.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Describe
{
    public function __construct(
        public string $description,
        public ?string $suggest = null,
    ) {}
}
