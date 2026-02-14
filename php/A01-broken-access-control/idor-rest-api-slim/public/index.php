<?php

declare(strict_types=1);

use App\Middleware\FakeAuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Routes\DocumentsRoutes;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate app
$app = AppFactory::create();

// Add pseudo authentication Middleware
$app->add(new FakeAuthMiddleware($app->getResponseFactory()));

// Add Error Handling Middleware
$app->addErrorMiddleware(true, false, false);

// Add Documents api
DocumentsRoutes::register($app);

// Add 'health' route callback
$app->get('/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['ok' => true]) . "\n");
    return $response->withHeader('Content-Type', 'application/json');
});

// Run application
$app->run();
