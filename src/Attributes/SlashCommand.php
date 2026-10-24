<?php

namespace Io\Discord\Attributes;

use Attribute;

/**
 * Makes a method a slash command. Its parameters of type string, int, float or bool become the command's
 * options, named after them (`$stationUrl` is `station_url`); one with a default or a nullable type is optional.
 * A parameter of type Command gets the command itself. What the method returns (a string or a Message) is the reply.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SlashCommand
{
    /** @param string|null $name the method's name in lower case when null */
    public function __construct(
        public string $description,
        public ?string $name = null,
    ) {}
}
