<?php

namespace Io\Discord\Tests;

use PHPUnit\Framework\TestCase;
use Tenma\Discord\{Bot, Intents};
use Io\Container;
use Io\Discord\Handlers;

final class HandlersTest extends TestCase
{
    private FakeConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new FakeConnection();
        $bot = new Bot('secret', Intents::Default, $this->connection);
        new Handlers($bot, new Container())->add(RadioCommands::class);
        RadioCommands::$ready = [];

        $bot->start();
        $this->connection->emit('READY', [
            'user' => ['id' => '1', 'username' => 'tenma', 'bot' => true],
            'application' => ['id' => 'app'],
        ]);
    }

    /** @param array<mixed> $data */
    private function interact(int $type, array $data): void
    {
        $this->connection->emit('INTERACTION_CREATE', [
            'id' => 'i1',
            'token' => 't1',
            'type' => $type,
            'guild_id' => 'g1',
            'channel_id' => 'c1',
            'member' => ['user' => ['id' => 'u1', 'username' => 'lento']],
            'data' => $data,
            'message' => ['id' => 'm1'],
        ]);
    }

    /** @return array<mixed> the first interaction callback */
    private function firstCallback(): array
    {
        return $this->connection->rest('POST', '/interactions/i1/t1/callback')[0] ?? [];
    }

    public function testParametersBecomeOptionsWithTheRequiredOnesFirst(): void
    {
        $options = [
            ['type' => 3, 'name' => 'genre', 'description' => 'genre', 'required' => true],
            ['type' => 3, 'name' => 'station_url', 'description' => 'Stream URL', 'required' => false, 'autocomplete' => true],
            ['type' => 4, 'name' => 'volume', 'description' => 'volume', 'required' => false],
        ];
        $command = ['name' => 'radio', 'description' => 'Plays a station', 'options' => $options];

        $this->assertSame([[$command]], $this->connection->rest('PUT', '/applications/app/commands'));
    }

    public function testACommandGetsItsOptionsAsArgumentsAndRepliesWithItsResult(): void
    {
        $this->interact(2, ['name' => 'radio', 'options' => [
            ['name' => 'genre', 'type' => 3, 'value' => 'J-pop'],
            ['name' => 'station_url', 'type' => 3, 'value' => 'https://listen.moe/stream'],
        ]]);

        $reply = ['type' => 4, 'data' => ['flags' => 0, 'content' => 'radio: J-pop https://listen.moe/stream at 100']];
        $this->assertSame($reply, $this->firstCallback());
    }

    public function testSuggestionsComeFromTheMethodDescribeNamesWithItsInjectedService(): void
    {
        $this->interact(4, ['name' => 'radio', 'options' => [
            ['name' => 'station_url', 'type' => 3, 'value' => 'j', 'focused' => true],
        ]]);

        $choices = ['choices' => [['name' => 'J-pop', 'value' => 'https://listen.moe/stream']]];
        $this->assertSame(['type' => 8, 'data' => $choices], $this->firstCallback());
    }

    public function testButtonsModalsAndEventsReachTheirMethods(): void
    {
        $this->interact(3, ['custom_id' => 'mute']);
        $this->assertSame('muted by lento', $this->callbackContent());

        $this->connection->calls = [];
        $this->interact(5, [
            'custom_id' => 'station',
            'components' => [['type' => 18, 'component' => ['type' => 4, 'custom_id' => 'name', 'value' => 'Radio X']]],
        ]);
        $this->assertSame('Thanks for Radio X in station', $this->callbackContent());

        $this->assertSame(['tenma'], RadioCommands::$ready);
    }

    private function callbackContent(): mixed
    {
        $data = $this->firstCallback()['data'] ?? [];

        return is_array($data) ? $data['content'] ?? null : null;
    }
}
