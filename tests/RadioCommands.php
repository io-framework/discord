<?php

namespace Io\Discord\Tests;

use Tenma\Discord\Events\Ready;
use Tenma\Discord\Interactions\{Autocomplete, Click, Command, Submit};
use Tenma\Discord\Message;
use Io\Attributes\Inject;
use Io\Discord\Attributes\{ButtonClick, Describe, ModalSubmit, OnEvent, SlashCommand};

/** Handlers as an app writes them. */
final class RadioCommands
{
    /** @var list<string> */
    public static array $ready = [];

    #[Inject]
    private Stations $stations;

    #[SlashCommand(description: 'Plays a station')]
    public function radio(
        Command $command,
        #[Describe('Stream URL', suggest: 'suggestStations')] ?string $stationUrl,
        string $genre,
        int $volume = 100,
    ): string {
        return "{$command->name}: $genre {$stationUrl} at $volume";
    }

    /** @return array<mixed> */
    public function suggestStations(Autocomplete $typed): array
    {
        return [...$this->stations->all, 'Broken' => ['not a value']];
    }

    #[ButtonClick('mute')]
    public function mute(Click $click): Message
    {
        return new Message("muted by {$click->user->name}");
    }

    #[ModalSubmit('station')]
    public function suggest(string $name, Submit $submit): string
    {
        return "Thanks for $name in {$submit->modalId}";
    }

    #[OnEvent]
    public function ready(Ready $ready): void
    {
        self::$ready[] = $ready->user->name;
    }
}
