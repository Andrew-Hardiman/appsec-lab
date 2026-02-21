<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Slim\App as SlimApp;
use App\Bootstrap;
use Slim\Psr7\Factory\ServerRequestFactory;

final class DocumentsAuthorizationTest extends TestCase 
{
    private SlimApp $app;

    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        $this->app = Bootstrap::createApp();
        $this->requestFactory = new ServerRequestFactory();
    }

    // A helper function to check for JSON decoding errors, making them explicit
    private function assertValidJson(string $rawBody): object
    {
        $decoded = json_decode($rawBody);

        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'Response body must be valid JSON. Body was: ' . $rawBody
        );

        $this->assertIsObject($decoded);

        return $decoded;
    }

    public function testNonOwnerCannotReadPrivateDocument() 
    {
        // Create a GET request to '/api/documents/{docId}' for document identifier '2'
        $request = $this->requestFactory->createServerRequest('GET', '/api/documents/2');
        
        // Pseudo Authentication - User 1
        $request = $request->withHeader('X-User-Id', '1');
    
        // Run request directly through app 
        $response = $this->app->handle($request);
        
        // Assert response status code is 403
        $status = $response->getStatusCode();
        $this->assertSame(403, $status);
        
        $rawBody = (string) $response->getBody();

        $decoded = $this->assertValidJson($rawBody);

        $this->assertObjectHasProperty('error', $decoded);
        $this->assertSame('forbidden', $decoded->error);
    }

    public function testOwnerCanReadOwnDocument()
    {
        // Create a GET request to '/api/documents/{docId}' for document identifier '1'
        $request = $this->requestFactory->createServerRequest('GET', '/api/documents/1');
        
        // Pseudo Authentication - User 1
        $request = $request->withHeader('X-User-Id', '1');
    
        // Run request directly through app 
        $response = $this->app->handle($request);

        // Assert response status code is 200
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);

        $rawBody = (string) $response->getBody();

        $decoded = $this->assertValidJson($rawBody);

        $this->assertObjectHasProperty('document', $decoded);
        $this->assertSame('This is user 1 personal information', $decoded->document);
    }

    public function testAnyUserCanReadPublicDocument()
    {
        // Create a GET request to '/api/documents/{docId}' for document identifier '3' (the public document)
        $request = $this->requestFactory->createServerRequest('GET', '/api/documents/3');
        
        // Pseudo Authentication - User 1
        $request = $request->withHeader('X-User-Id', '1');
    
        // Run request directly through app 
        $response = $this->app->handle($request);

        // Assert response status code is 200
        $status = $response->getStatusCode();
        $this->assertSame(200, $status);

        $rawBody = (string) $response->getBody();

        $decoded = $this->assertValidJson($rawBody);

        $this->assertObjectHasProperty('document', $decoded);
        $this->assertSame('None sensitive information for everyone to read', $decoded->document);
    }

    public function testMissingDocumentReturns404() 
    {
        // Create a GET request to '/api/documents/{docId}' for document identifier '9999' (Not Found)
        $request = $this->requestFactory->createServerRequest('GET', '/api/documents/9999');

        // Pseudo Authentication - User 1
        $request = $request->withHeader('X-User-Id', '1');
    
        // Run request directly through app 
        $response = $this->app->handle($request);

        // Assert response status code is 404
        $status = $response->getStatusCode();
        $this->assertSame(404, $status);

        $rawBody = (string) $response->getBody();

        $decoded = $this->assertValidJson($rawBody);

        $this->assertObjectHasProperty('error', $decoded);
        $this->assertSame('Document not found', $decoded->error);
    }

    public function testUnauthenticatedRequestReturns401() 
    {
        // Create a GET request to '/api/documents/{docId}' for document identifier '1'
        $request = $this->requestFactory->createServerRequest('GET', '/api/documents/1');

        // Run request directly through app 
        $response = $this->app->handle($request);

        // Assert response status code is 401
        $status = $response->getStatusCode();
        $this->assertSame(401, $status);

        $rawBody = (string) $response->getBody();

        $decoded = $this->assertValidJson($rawBody);

        $this->assertObjectHasProperty('error', $decoded);
        $this->assertSame('unauthenticated', $decoded->error);
    }
}