<?php
declare(strict_types=1);

namespace App\Tests;

class IngestionControllerTest extends AuthenticatedApiTestCase
{
    public function testKnowledgeBaseEndpoints(): void
    {
        $client = $this->createAuthenticatedClient('admin-kb@test.com', 'password', ['ROLE_USER']);

        $client->request('GET', '/api/knowledge-base');
        $this->assertResponseIsSuccessful();

        $client->request('DELETE', '/api/knowledge-base/00000000-0000-0000-0000-000000000000');
        $this->assertResponseStatusCodeSame(404);
        
        $client->request('POST', '/api/ingest');
        $this->assertResponseStatusCodeSame(400);
    }
}