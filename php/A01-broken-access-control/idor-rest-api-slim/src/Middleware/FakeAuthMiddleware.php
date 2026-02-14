<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Lab note: authentication is out of scope for this case study.
 * We simulate an authenticated principal via `X-User-Id` to keep the demo focused on authorization (IDOR)
 * and make reproduction easy with curl. In real systems the user identity comes from a verified session
 * or validated token claims (e.g., session cookie or JWT), not a client-supplied header.
 */
final class FakeAuthMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(Request $request, RequestHandler $requestHandler): Response
    {
        $userIDHeader = trim($request->getHeaderLine('X-User-Id'));

        if(empty($userIDHeader)) {
            $response = $this->responseFactory->createResponse(401);

            $response = $response->withHeader('Content-Type', 'application/json');

            $response->getBody()->write(json_encode(['error' => 'unauthenticated']));
            return $response;
        } else {
            $requestWithUser = $request->withAttribute('user_id', $userIDHeader);
            return $requestHandler->handle($requestWithUser);
        } 
    }
}
