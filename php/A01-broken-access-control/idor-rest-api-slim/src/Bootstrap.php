<?php

declare(strict_types=1);

namespace App;

use App\Middleware\FakeAuthMiddleware;
use App\Routes\DocumentsRoutes;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;
use Throwable;

final class Bootstrap
{
    public static function createApp(): App
    {
        $app = SlimAppFactory::create();

        $customNotFoundHandler = function (Request $request, Throwable $exception, bool $displayErrorDetails) use ($app): Response {
            $response = $app->getResponseFactory()->createResponse(404);
            $response->getBody()->write(json_encode(['error' => 'Not found']) . "\n");
            return $response->withHeader('Content-Type', 'application/json');
        };

        // Pseudo authentication Middleware
        $app->add(new FakeAuthMiddleware($app->getResponseFactory()));

        // Error Handling Middleware
        $errorMiddleware = $app->addErrorMiddleware(true, false, false);
        $errorMiddleware->setErrorHandler(\Slim\Exception\HttpNotFoundException::class, $customNotFoundHandler);

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
