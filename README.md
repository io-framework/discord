# Io Discord

A Discord bot inside a [Io](https://github.com/io-framework/framework) app: slash commands, buttons, modals and
gateway events are attributes on your own classes, with Io's dependency injection. The bot runs on
[Tenma](https://github.com/tenma-runtime/tenma) next to the app's routes, through
[tenma/discord](https://github.com/tenma-runtime/discord).

```php
use Tenma\Discord\Interactions\Command;
use Io\Discord\Attributes\{Describe, SlashCommand};
use Io\Discord\DiscordPlugin;
use Io\App;

final class GreetCommands
{
    #[SlashCommand(
        description: 'Says hello'
    )]
    public function hello(Command $command, #[Describe('Who to greet')] ?string $name): string
    {
        return 'Hello ' . ($name ?? $command->user->name);
    }
}

$app = App::create();
$app->plugin(new DiscordPlugin([GreetCommands::class]));
$app->attach([]);
$app->serve();
```

## Install

```bash
composer require io/discord
vendor/bin/tenma-discord install
DISCORD_TOKEN=... tenma index.php
```

`tenma-discord install` downloads the native plugin and adds it to the project's `tenma.json`. The token comes from
`DISCORD_TOKEN` in the [Environment](https://github.com/io-framework/framework/blob/main/README.md#environment), or from
`DiscordOptions`:

```php
$app->configure(function (DiscordOptions $discord) {
    $discord->intents = Intents::Default | Intents::GuildMessages | Intents::MessageContent;
});
```

The bot connects only when the app serves under Tenma: under FPM, in tests and in CLI tasks
(`tenma index.php db:migrate`) it stays offline.

## Handlers

A handler class is a plain class; the plugin takes the list. Each call gets its own copy of the class, made from the
container's instance with its `#[Inject]` services, as a controller does per request. What a method returns (a string
or a `Tenma\Discord\Message`) is the reply.

- `#[SlashCommand(description: ..., name: ...)]`: the method is a slash command, named after the method unless
  `name` says otherwise. Its `string`, `int`, `float` and `bool` parameters are the command's options:
  `$stationUrl` is the option `station_url`, a nullable parameter or one with a default is optional.
  `#[Describe('Text')]` gives an option its description; `#[Describe('Text', suggest: 'method')]` lets a method of
  the same class suggest values while the user types (it gets an `Autocomplete` and returns values or
  names => values).
- `#[ButtonClick('vote')]`: clicks on buttons with the id `vote` or `vote:...`.
- `#[ModalSubmit('feedback')]`: sent modals; a `string` parameter gets the field of its name.
- `#[OnEvent]`: a listener for the event its parameter's type names, `ready(Ready $ready)`.

A parameter of the interaction's type (`Command`, `Click`, `Submit`) gets the interaction, one of type `Bot` the bot.
The [tenma/discord README](https://github.com/tenma-runtime/discord/blob/main/README.md) covers messages, embeds,
buttons, modals, voice and the REST API.

## The bot in the app

The plugin registers the `Tenma\Discord\Bot` in the container, so a route or a service can post into Discord:

```php
#[Controller('/announce')]
final class AnnounceController
{
    #[Inject]
    private Bot $bot;

    #[Post]
    public function announce(#[Query] string $channel, #[Query] string $text): void
    {
        $this->bot->rest->send($channel, $text);
    }
}
```

Another plugin adds its own handler classes in `boot(Handlers $handlers)` with `$handlers->add(...)`.

Under `tenma --workers N` every worker runs the bot; each gets the events of its share of the guilds. State that all
workers need belongs in Postgres or Redis.

## Demo

[`demo/`](demo) is a radio bot as a small Io project: `/radio` with station suggestions plays in your voice
channel, and a status page shows what plays where.

```bash
cd demo && composer install && vendor/bin/tenma-discord install
cp .env.example .env    # and fill in DISCORD_TOKEN
tenma index.php         # the bot, and the status page on http://localhost:8080
```

## License

MIT or Apache-2.0, at your option.
