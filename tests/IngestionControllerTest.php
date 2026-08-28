<?php
declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Entity\ClinicalDocument;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class IngestionControllerTest extends AuthenticatedApiTestCase
{
    public function testKnowledgeBaseEndpoints(): void
    {
        $client = $this->createAuthenticatedClient('admin-kb@test.com', 'password', ['ROLE_USER']);

        $client->request('GET', '/api/knowledge-base');
        $this->assertResponseIsSuccessful();

        $client->request('DELETE', '/api/knowledge-base/00000000-0000-0000-0000-000000000000');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $client->request('POST', '/api/ingest');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testIngestionUploadsSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('ingestion-user@test.com');
        
        $filePath = tempnam(sys_get_temp_dir(), 'tst');
        // Usamos un contenido mínimo de PDF válido para que finfo() lo reconozca.
        $pdfContent = <<<PDF
%PDF-1.0
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/MediaBox[0 0 3 3]>>endobj
trailer<</Root 1 0 R>>
PDF;
        file_put_contents($filePath, $pdfContent);
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile($filePath, 'test.pdf', 'application/pdf', null, true);

        $client->request('POST', '/api/ingest', [], ['file' => $uploadedFile]);

        $this->assertResponseIsSuccessful();
    }

    public function testIngestionDeletesSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('ingestion-deleter@test.com', 'password', ['ROLE_USER']);

        $doc = new ClinicalDocument();
        $doc->setFileName('doc-to-delete.pdf');
        $this->em->persist($doc);
        $this->em->flush();

        $client->request('DELETE', '/api/knowledge-base/' . $doc->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    
    public function testIngestionHandlesInvalidFiles(): void
    {
        $client = $this->createAuthenticatedClient('ingestion-invalid@test.com');

        // Test invalid MIME type
        $filePath = tempnam(sys_get_temp_dir(), 'tst');
        file_put_contents($filePath, 'dummy content');
        $uploadedFile = new UploadedFile($filePath, 'test.txt', 'text/plain', null, true);
        $client->request('POST', '/api/ingest', [], ['file' => $uploadedFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Test invalid (corrupt) file upload
        $corruptFile = new UploadedFile(tempnam(sys_get_temp_dir(), 'tst_err'), 'corrupt.pdf', 'application/pdf', \UPLOAD_ERR_CANT_WRITE, true);
        $client->request('POST', '/api/ingest', [], ['file' => $corruptFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}