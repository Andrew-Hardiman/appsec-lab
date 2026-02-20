<?php

declare(strict_types=1);

use App\Bootstrap;

require __DIR__ . '/../vendor/autoload.php';

$app = Bootstrap::createApp();

// Run application
$app->run();
