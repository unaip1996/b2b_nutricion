<?php
declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Controller\DashboardController;
use App\Infrastructure\Controller\DietController;
use App\Infrastructure\Controller\IngestionController;
use App\Infrastructure\Controller\PatientController;
use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\DocumentChunk;
use App\Infrastructure\Repository\ClinicalDocumentRepository;
use App\Infrastructure\Repository\DocumentChunkRepository;
use App\Infrastructure\Repository\PatientRepository;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class GreenMasterTest extends TestCase
{
    public function testDashboardException(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willThrowException(new \Exception('Test'));
        $controller = new DashboardController();
        $this->assertEquals(500, $controller->getStats($em)->getStatusCode());
    }

    public function testDietControllerExceptionsAndSuccess(): void
    {
        $useCase = $this->createStub(GenerateClinicalDietUseCase::class);
        $useCase->method('execute')->willReturn('{"ok": true}');
        $logger = $this->createStub(LoggerInterface::class);
        $controller = new DietController($useCase, $logger);
        
        $reqGenerate = new Request([], [], [], [], [], [], json_encode(['patientId'=>'1','query'=>'t']));
        $this->assertEquals(200, $controller->generateDiet($reqGenerate, $useCase)->getStatusCode());

        $useCaseThrow = $this->createStub(GenerateClinicalDietUseCase::class);
        $useCaseThrow->method('execute')->willThrowException(new \Exception('Test'));
        $controllerThrow = new DietController($useCaseThrow, $logger);
        $this->assertEquals(500, $controllerThrow->generateDiet($reqGenerate, $useCaseThrow)->getStatusCode());
        
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willThrowException(new \Exception('Test'));

        $this->assertEquals(500, $controller->listPatientDiets('1', $em)->getStatusCode());
        $this->assertEquals(500, $controller->getDietDetail('1', $em)->getStatusCode());
        $this->assertEquals(500, $controller->deleteDiet('1', $em)->getStatusCode());
        
        $reqPut = new Request([], [], [], [], [], [], '{}');
        $this->assertEquals(500, $controller->updateDiet('1', $reqPut, $em)->getStatusCode());
    }

    public function testIngestionControllerLogic(): void
    {
        $chunkRepo = $this->createStub(DocumentChunkRepository::class);
        $useCase = $this->createStub(IngestClinicalDocumentUseCase::class);
        $docRepo = $this->createStub(ClinicalDocumentRepository::class);
        $controller = new IngestionController($chunkRepo, $useCase, $docRepo);

        // Fix de Reflection para el UUID y la fecha
        $doc = new ClinicalDocument();
        $doc->setFileName('test.pdf');
        $reflection = new \ReflectionClass($doc);
        
        $propId = $reflection->getProperty('id');
        $propId->setValue($doc, \Symfony\Component\Uid\Uuid::fromString('00000000-0000-0000-0000-000000000000'));
        
        $propDate = $reflection->getProperty('ingestedAt');
        $propDate->setValue($doc, new \DateTimeImmutable());
        
        $docRepo->method('findAllActive')->willReturn([$doc]);
        $this->assertEquals(200, $controller->listDocuments()->getStatusCode());

        $docRepoEx = $this->createStub(ClinicalDocumentRepository::class);
        $docRepoEx->method('findAllActive')->willThrowException(new \Exception('Test'));
        $controllerEx = new IngestionController($chunkRepo, $useCase, $docRepoEx);
        $this->assertEquals(500, $controllerEx->listDocuments()->getStatusCode());

        $em = $this->createStub(EntityManagerInterface::class);
        $repo = $this->createStub(EntityRepository::class);
        $repo->method('find')->willReturn(null);
        $em->method('getRepository')->willReturn($repo);
        $this->assertEquals(404, $controller->deleteDocument('1', $em)->getStatusCode());

        $emEx = $this->createStub(EntityManagerInterface::class);
        $emEx->method('getRepository')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->deleteDocument('1', $emEx)->getStatusCode());

        $useCase->method('execute')->willThrowException(new \Exception('Test'));
        $file = $this->createStub(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getMimeType')->willReturn('application/pdf');
        $req = new Request();
        $req->files->set('file', $file);
        $this->assertEquals(500, $controller->upload($req)->getStatusCode());
    }

    public function testPatientControllerExceptions(): void
    {
        $repo = $this->createStub(PatientRepository::class);
        $auth = $this->createStub(AuthorizationCheckerInterface::class);
        $token = $this->createStub(TokenStorageInterface::class);
        $controller = new PatientController($repo, $auth, $token);
        
        $em = $this->createStub(EntityManagerInterface::class);
        
        $patient = clone (new Patient());
        $repo->method('find')->willReturn($patient);
        
        $reqUpdate = new Request([], [], [], [], [], [], 'invalid');
        $this->assertEquals(500, $controller->update('1', $reqUpdate, $em)->getStatusCode());

        $reqCreateValid = new Request([], [], [], [], [], [], '{}');
        $em->method('persist')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->create($reqCreateValid, $em)->getStatusCode());

        $em->method('flush')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->delete('1', $em)->getStatusCode());
    }

    public function testGenerateDietUseCaseNotFound(): void
    {
        $patientRepo = $this->createStub(PatientRepository::class);
        $chunkRepo = $this->createStub(DocumentChunkRepository::class);
        $embed = $this->createStub(EmbeddingGeneratorInterface::class);
        $llm = $this->createStub(LlmInferenceInterface::class);
        $em = $this->createStub(EntityManagerInterface::class);

        $useCase = new GenerateClinicalDietUseCase($patientRepo, $chunkRepo, $embed, $llm, $em);
        $patientRepo->method('find')->willReturn(null);
        
        $this->expectException(\InvalidArgumentException::class);
        $useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function testGenerateDietUseCaseInvalidJson(): void
    {
        $patientRepo = $this->createStub(PatientRepository::class);
        $chunkRepo = $this->createStub(DocumentChunkRepository::class);
        $embed = $this->createStub(EmbeddingGeneratorInterface::class);
        $llm = $this->createStub(LlmInferenceInterface::class);
        $em = $this->createStub(EntityManagerInterface::class);

        $useCase = new GenerateClinicalDietUseCase($patientRepo, $chunkRepo, $embed, $llm, $em);
        
        $patient = new Patient();
        $patientRepo->method('find')->willReturn($patient);
        $llm->method('generateText')->willReturn('INVALID JSON');
        
        $this->expectException(\RuntimeException::class);
        $useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
    }
}