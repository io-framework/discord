<?php

namespace Demo\Controllers;

use Demo\Services\{OnAir, Stations};
use Io\Attributes\{Controller, Get, Inject};
use Io\Http\View;

/** The page that shows what the bot plays where. */
#[Controller]
final class StatusController
{
    #[Inject]
    private OnAir $onAir;

    #[Inject]
    private Stations $stations;

    #[Get('/')]
    public function index(): View
    {
        $playing = array_map($this->stations->name(...), $this->onAir->playing);

        return new View('Status', $playing);
    }
}
