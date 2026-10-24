<?php

namespace Demo\Services;

/**
 * What plays in which guild, as far as this worker has seen: the bot keeps it, the status page shows it. With
 * `tenma --workers N` each worker sees its own guilds; shared state would go into Redis or Postgres.
 */
final class OnAir
{
    /** @var array<string, string> guild id => stream URL */
    public private(set) array $playing = [];

    public function started(string $guildId, string $url): void
    {
        $this->playing[$guildId] = $url;
    }

    public function stopped(string $guildId): void
    {
        unset($this->playing[$guildId]);
    }
}
