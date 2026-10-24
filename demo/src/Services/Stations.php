<?php

namespace Demo\Services;

/** The stations the bot suggests. */
final class Stations
{
    /** @var array<string, string> name => stream URL */
    public array $all = [
        'listen.moe J-pop' => 'https://listen.moe/stream',
        'listen.moe K-pop' => 'https://listen.moe/kpop/stream',
    ];

    /** The station /radio plays when the user names none. */
    public function first(): string
    {
        return current($this->all) ?: '';
    }

    /** A station's name, or its URL when it is none of these. */
    public function name(string $url): string
    {
        return array_search($url, $this->all, true) ?: $url;
    }
}
