<?php

declare(strict_types=1);

namespace App;

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

        // Error Handling Middleware
        $errorMiddleware = $app->addErrorMiddleware(true, false, false);
        $errorMiddleware->setErrorHandler(\Slim\Exception\HttpNotFoundException::class, $customNotFoundHandler);

        // Health route
        $app->get('/health', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode(['ok' => true]) . "\n");
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Only register diagnostics in dev.
        $appEnv = getenv('APP_ENV') ?: 'prod';

        if ($appEnv === 'dev') {
            // Returns `phpinfo()` output to the caller.
            $app->get('/debug/phpinfo', function (Request $request, Response $response) {
                ob_start();
                phpinfo();
                $html = ob_get_clean();

                $response->getBody()->write($html);
                return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
            });

            // Returns the application's route table to caller.
            $app->get('/debug/routes', function (Request $request, Response $response) use ($app) {
                $routes = [];

                foreach ($app->getRouteCollector()->getRoutes() as $route) {
                    $routes[] = [
                        'methods' => $route->getMethods(),
                        'pattern' => $route->getPattern(),
                        'name'    => $route->getName(),
                    ];
                }

                $response->getBody()->write(json_encode(['routes' => $routes]) . "\n");
                return $response->withHeader('Content-Type', 'application/json');
            });
        }
        
        return $app;
    }
}
