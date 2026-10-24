<?php
// The radio as an Io app: the bot's handlers, the status page and their services. index.php serves it and the
// tests load it too.

require __DIR__ . '/vendor/autoload.php';

use Demo\Controllers\StatusController;
use Demo\Discord\Radio;
use Io\Discord\DiscordPlugin;
use Io\Options\{EnvironmentOptions, RendererOptions};
use Io\App;

$app = App::create();

$app->configure(function (EnvironmentOptions $env, RendererOptions $views) {
    // DISCORD_TOKEN from .env next to this file
    $env->load(__DIR__);
    $views->directory = __DIR__ . '/views';
});

$app->plugin(new DiscordPlugin([Radio::class]));
$app->attach([StatusController::class]);

return $app;
