<?php

namespace Demo\Discord;

use Demo\Services\{OnAir, Stations};
use Tenma\Discord\{Bot, Button, ButtonStyle, Embed, Message};
use Tenma\Discord\Events\{Ready, TrackEnded, TrackStarted};
use Tenma\Discord\Interactions\{Autocomplete, Click, Command};
use Io\Attributes\Inject;
use Io\Discord\Attributes\{ButtonClick, Describe, OnEvent, SlashCommand};

/** /radio plays a station in the caller's voice channel; the button under it leaves again. */
final class Radio
{
    #[Inject]
    private Bot $bot;

    #[Inject]
    private Stations $stations;

    #[Inject]
    private OnAir $onAir;

    #[SlashCommand(
        description: 'Plays a radio station in your voice channel'
    )]
    public function radio(Command $command, #[Describe('Station', suggest: 'suggest')] ?string $station): Message
    {
        if ($command->voiceChannel === null || $command->guildId === null) {
            return new Message('Join a voice channel first.', ephemeral: true);
        }

        // Joining takes longer than the 3 seconds Discord waits
        $command->defer();
        $url = $station ?? $this->stations->first();
        $this->bot->voice($command->guildId)->join($command->voiceChannel)->play($url);

        $nowPlaying = new Embed(title: 'On air', description: $this->stations->name($url), color: 0xE91E63);

        return new Message(embeds: [$nowPlaying], buttons: [new Button('leave', 'Leave', ButtonStyle::Danger)]);
    }

    /**
     * The stations whose name contains what the user typed.
     * @return array<string, string>
     */
    public function suggest(Autocomplete $typed): array
    {
        return array_filter(
            $this->stations->all,
            fn (string $name) => stripos($name, $typed->value) !== false,
            ARRAY_FILTER_USE_KEY,
        );
    }

    #[ButtonClick('leave')]
    public function leave(Click $click): void
    {
        $this->bot->voice((string) $click->guildId)->leave();
        $click->update(new Message('Off air', embeds: [], buttons: []));
    }

    #[OnEvent]
    public function ready(Ready $ready): void
    {
        echo "online as {$ready->user->name}\n";
    }

    #[OnEvent]
    public function started(TrackStarted $track): void
    {
        $this->onAir->started($track->guildId, $track->url);
    }

    #[OnEvent]
    public function ended(TrackEnded $track): void
    {
        $this->onAir->stopped($track->guildId);
    }
}
