<?php
// tenma index.php: the status page on PORT (8080) and the bot, in one process.

/** @var Io\App $app */
$app = require __DIR__ . '/app.php';
$app->serve();
