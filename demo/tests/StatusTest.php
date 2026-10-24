<?php

namespace Demo\Tests;

use Demo\Services\OnAir;
use Io\Testing\TestCase;

/** The app as app.php builds it; outside of Tenma the bot stays offline. */
final class StatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require __DIR__ . '/../app.php';
    }

    public function testTheStatusPageShowsWhatPlaysWhere(): void
    {
        $this->get('/')->assertOk()->assertSee('Nothing on air');

        $this->app()->get(OnAir::class)->started('42', 'https://listen.moe/kpop/stream');

        $this->get('/')->assertSee('listen.moe K-pop')->assertSee('in guild 42');
    }
}
