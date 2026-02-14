<?php
declare(strict_types=1);

use App\Middleware\FakeAuthMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate app
$app = AppFactory::create();

// Add pseudo authentication Middleware
$app->add(new FakeAuthMiddleware($app->getResponseFactory()));

// Add Error Handling Middleware
$app->addErrorMiddleware(true, false, false);

// Add 'health' route callback
$app->get('/health', function (Request $request, Response $response, array $args) {
    $response->getBody()->write(json_encode(['ok' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Regster api route to fetch documents from a database, with IDOR
$app->get('/api/documents/{docId}', function (Request $request, Response $response, array $args) {
    // In-memory dataset for case-study example usage
    $documentDatabase = [
    	['ownerUserId' => 1, 'docId' => 1, 'document' => 'This is user 1 personal information'],
        ['ownerUserId' => 2, 'docId' => 2, 'document' => 'This is user 2 personal infromation'],
        ['ownerUserId' => null, 'docId' => 3, 'document' => 'None sensitive information for everyone to read']
    ];

    $documentID = (int)$args['docId'];
    $documentFound = false;

    foreach($documentDatabase as $documentRow) {
        if($documentRow['docId'] === $documentID) {
            $documentFound = true;
	    $documentData = $documentRow['document'];

            break;
	}
    }

    if($documentFound) {
        $response->getBody()->write(json_encode(["document" => $documentData]));
    } else {
        $response->getBody()->write(json_encode(['error' => 'Document not found']));
        $response = $response->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json');
});

// Run application
$app->run();
