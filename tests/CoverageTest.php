<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Este fichero contiene tests adicionales diseñados específicamente
 * para cubrir los "casos borde" y ramas de código que no se cubren
 * en los tests principales, con el objetivo de aumentar la cobertura de código.
 */
class CoverageTest extends AuthenticatedApiTestCase
{
    // =========================================================================
    // UserController Coverage
    // =========================================================================

    public function testAdminCannotRemoveOwnAdminRole(): void
    {
        $adminClient = $this->createAuthenticatedClient('self-admin@test.com', 'password', ['ROLE_ADMIN']);
        /** @var User $adminUser */
        $adminUser = $this->em->getRepository(User::class)->findOneBy(['email' => 'self-admin@test.com']);
        $adminId = $adminUser->getId();

        $adminClient->request(
            'PUT',
            '/api/users/' . $adminId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['roles' => ['ROLE_USER']]) // Intentando quitar ROLE_ADMIN
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserEndpointsReturn404ForInvalidId(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-404@test.com', 'password', ['ROLE_ADMIN']);
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $adminClient->request('PUT', '/api/users/' . $invalidUuid, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'test@test.com']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $adminClient->request('DELETE', '/api/users/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUserActionsHandleMalformedJson(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-json-err@test.com', 'password', ['ROLE_ADMIN']);
        $passwordHasher = $adminClient->getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('some-user-for-update@test.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $this->em->persist($user);
        $this->em->flush();

        $malformedJson = '{"email": "test@test.com",}'; // JSON inválido

        $adminClient->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $adminClient->request('PUT', '/api/users/' . $user->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    // =========================================================================
    // PatientController Coverage
    // =========================================================================

    public function testPatientEndpointsReturn404ForInvalidId(): void
    {
        $client = $this->createAuthenticatedClient('patient-user-404@test.com');
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $client->request('PUT', '/api/patients/' . $invalidUuid, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'test']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('DELETE', '/api/patients/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatientCreateHandlesMalformedJson(): void
    {
        $client = $this->createAuthenticatedClient('patient-json-err@test.com');
        $malformedJson = '{"name": "test",}';

        $client->request('POST', '/api/patients', [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // =========================================================================
    // DietController Coverage
    // =========================================================================

    public function testDietEndpointsReturn404ForInvalidId(): void
    {
        $client = $this->createAuthenticatedClient('diet-user-404@test.com');
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $client->request('GET', '/api/diets/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('PUT', '/api/diets/' . $invalidUuid, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'test']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGenerateDietSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('diet-generator@test.com');
        $patient = new Patient();
        $patient->setName('Test Patient for Diet Gen');
        $patient->setMedicalHistoryNumber('CG-DIET-GEN-1');
        $patient->setGender('Masculino');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        $this->em->persist($patient);
        $this->em->flush();
        
        // Mock the GenerateClinicalDietUseCase
        $useCaseMock = $this->createMock(GenerateClinicalDietUseCase::class);
        $useCaseMock->expects($this->once())
            ->method('execute')
            ->willReturn('{"status":"success"}');
        self::getContainer()->set(GenerateClinicalDietUseCase::class, $useCaseMock);

        $client->request(
            'POST',
            '/api/diets/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['patientId' => $patient->getId(), 'query' => 'test query'])
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('{"status":"success"}', $response['data']['dietary_proposal']);
    }

    // =========================================================================
    // IngestionController Coverage
    // =========================================================================

    public function testIngestionUploadsSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('ingestion-user@test.com');
        
        // Mock the IngestClinicalDocumentUseCase
        $useCaseMock = $this->createMock(IngestClinicalDocumentUseCase::class);
        $useCaseMock->expects($this->once())->method('execute');
        self::getContainer()->set(IngestClinicalDocumentUseCase::class, $useCaseMock);

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
        $uploadedFile = new UploadedFile($filePath, 'test.pdf', 'application/pdf', null, true);

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

    // =========================================================================
    // DashboardController Coverage
    // =========================================================================

    public function testDashboardStatsCoversAllStatuses(): void
    {
        $client = $this->createAuthenticatedClient('dashboard-user@test.com');
        $patient = new Patient();
        $patient->setName('Dash Patient');
        $patient->setMedicalHistoryNumber('CG-DASH-1');
        $patient->setGender('Femenino');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        $this->em->persist($patient);

        $dietActive = new DietaryPlan();
        $dietActive->setPatient($patient)->setName('Activa')->setStartDate(new \DateTimeImmutable('-1 day'))->setEndDate(new \DateTimeImmutable('+1 day'));
        $this->em->persist($dietActive);

        $dietExpired = new DietaryPlan();
        $dietExpired->setPatient($patient)->setName('Expirada')->setStartDate(new \DateTimeImmutable('-2 days'))->setEndDate(new \DateTimeImmutable('-1 day'));
        $this->em->persist($dietExpired);

        $dietScheduled = new DietaryPlan();
        $dietScheduled->setPatient($patient)->setName('Programada')->setStartDate(new \DateTimeImmutable('+1 day'))->setEndDate(new \DateTimeImmutable('+2 days'));
        $this->em->persist($dietScheduled);

        $this->em->flush();

        $client->request('GET', '/api/dashboard/stats');
        $this->assertResponseIsSuccessful();
    }
}