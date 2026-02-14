<?php

declare(strict_types=1);

namespace App\Routes;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class DocumentsRoutes
{
    public static function register($app): void
    {
        // Register api route to fetch documents from a database, with IDOR
        $app->get('/api/documents/{docId}', function (Request $request, Response $response, array $args) {
            // In-memory dataset for case-study example usage
            $documentDatabase = [
                ['ownerUserId' => 1, 'docId' => 1, 'document' => 'This is user 1 personal information'],
                ['ownerUserId' => 2, 'docId' => 2, 'document' => 'This is user 2 personal information'],
                ['ownerUserId' => null, 'docId' => 3, 'document' => 'None sensitive information for everyone to read']
            ];

            $documentID = (int)$args['docId'];

            $documentFound = false;
            $documentData = null;
            $ownerUserId = null;

            foreach($documentDatabase as $documentRow) {
                if($documentRow['docId'] === $documentID) {
                    $documentFound = true;
                    $documentData = $documentRow['document'];
                    $ownerUserId = $documentRow['ownerUserId'];

                    break;
                }
            }

            if($documentFound) {
                $currentUserId = (int) $request->getAttribute('user_id');

                if($ownerUserId !== null && $currentUserId !== (int) $ownerUserId) {
                    $response = $response->withStatus(403);
                    $response->getBody()->write(json_encode(['error' => 'forbidden']) . "\n");
                } else {
                    $response->getBody()->write(json_encode(["document" => $documentData]) . "\n");
                }
            } else {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(['error' => 'Document not found']) . "\n");
            }

            return $response->withHeader('Content-Type', 'application/json');
        });
    }
}