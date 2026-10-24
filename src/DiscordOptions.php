<?php

namespace Io\Discord;

use Tenma\Discord\Intents;

/** The bot's settings, set with `$app->configure(fn (DiscordOptions $discord) => ...)`. */
class DiscordOptions
{
    /** The bot's token from the developer portal; DISCORD_TOKEN from the Environment when null. */
    public ?string $token = null;

    /** The gateway intents (Tenma\Discord\Intents); message content and members are privileged. */
    public int $intents = Intents::Default;
}
