<?php

namespace Io\Discord;

use LogicException;
use Tenma\Discord\Bot;
use Io\Container;
use Io\Environment;
use Io\Plugin\PluginBase;

/**
 * A Discord bot in an Io app: `$app->plugin(new DiscordPlugin([RadioCommands::class]))`. Under Tenma every
 * worker connects its Bot at attach(), and the bot runs next to the app's routes. Services and routes reach the
 * bot with `#[Inject] private Bot $bot`.
 */
final class DiscordPlugin extends PluginBase
{
    /** @param list<class-string> $handlers classes with #[SlashCommand], #[ButtonClick], #[ModalSubmit] or #[OnEvent] methods */
    public function __construct(
        private readonly array $handlers = [],
    ) {}

    public function name(): string
    {
        return 'discord';
    }

    public function boot(Container $container, DiscordOptions $options, Environment $environment): void
    {
        $token = $options->token ?? $environment->get('DISCORD_TOKEN');
        $bot = new Bot(is_string($token) ? $token : '', $options->intents);
        $handlers = new Handlers($bot, $container)->add(...$this->handlers);
        $container->set($bot);
        $container->set($handlers);

        // Only a server connects: under FPM there is no plugin, and a CLI task (`tenma index.php db:migrate`) needs no bot
        global $argc;
        if (!class_exists(\Http\Server::class, false) || $argc >= 2) {
            return;
        }
        if (!is_string($token) || $token === '') {
            throw new LogicException('The Discord bot needs a token: DISCORD_TOKEN, or DiscordOptions::$token');
        }
        $bot->start();
    }
}
