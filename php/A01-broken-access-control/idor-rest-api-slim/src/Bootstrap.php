<?php

declare(strict_types=1);

namespace App;

use App\Middleware\FakeAuthMiddleware;
use App\Routes\DocumentsRoutes;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class Bootstrap
{
    public static function createApp(): App
    {
        $app = SlimAppFactory::create();

        // Pseudo authentication Middleware
        $app->add(new FakeAuthMiddleware($app->getResponseFactory()));

        // Error Handling Middleware
        $app->addErrorMiddleware(true, false, false);

        // Documents API
        DocumentsRoutes::register($app);

        // Health route
        $app->get('/health', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode(['ok' => true]) . "\n");
            return $response->withHeader('Content-Type', 'application/json');
        });

        return $app;
    }
}
