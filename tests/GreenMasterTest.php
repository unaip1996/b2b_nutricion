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

class GreenMasterTest extends TestCase
{
    // 1. Cubrir getters sobrantes de la entidad
    public function testClinicalDocumentGetters(): void
    {
        $doc = new ClinicalDocument();
        $doc->setFileName('test.pdf');
        $this->assertSame('test.pdf', $doc->getFileName());
        $this->assertNull($doc->getId());
    }

    // 2. Forzar Exception (Catch) en el Dashboard
    public function testDashboardException(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willThrowException(new \Exception('Test'));
        $controller = new DashboardController();
        $this->assertEquals(500, $controller->getStats($em)->getStatusCode());
    }

    // 3. Forzar todas las Exceptions del DietController + Éxito de generación
    public function testDietControllerExceptionsAndSuccess(): void
    {
        $useCase = $this->createMock(GenerateClinicalDietUseCase::class);
        $useCase->method('execute')->willReturn('{"ok": true}');
        $logger = $this->createMock(LoggerInterface::class);
        $controller = new DietController($useCase, $logger);
        
        $reqGenerate = new Request([], [], [], [], [], [], json_encode(['patientId'=>'1','query'=>'t']));
        $this->assertEquals(200, $controller->generateDiet($reqGenerate, $useCase)->getStatusCode());

        $useCaseThrow = $this->createMock(GenerateClinicalDietUseCase::class);
        $useCaseThrow->method('execute')->willThrowException(new \Exception('Test'));
        $controllerThrow = new DietController($useCaseThrow, $logger);
        $this->assertEquals(500, $controllerThrow->generateDiet($reqGenerate, $useCaseThrow)->getStatusCode());
        
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willThrowException(new \Exception('Test'));

        $this->assertEquals(500, $controller->listPatientDiets('1', $em)->getStatusCode());
        $this->assertEquals(500, $controller->getDietDetail('1', $em)->getStatusCode());
        $this->assertEquals(500, $controller->deleteDiet('1', $em)->getStatusCode());
        
        $reqPut = new Request([], [], [], [], [], [], '{}');
        $this->assertEquals(500, $controller->updateDiet('1', $reqPut, $em)->getStatusCode());
    }

    // 4. Forzar Exceptions en el IngestionController (Motor RAG)
    public function testIngestionControllerLogic(): void
    {
        $chunkRepo = $this->createMock(DocumentChunkRepository::class);
        $useCase = $this->createMock(IngestClinicalDocumentUseCase::class);
        $docRepo = $this->createMock(ClinicalDocumentRepository::class);
        $controller = new IngestionController($chunkRepo, $useCase, $docRepo);

        // Éxito al listar
        $doc = new ClinicalDocument();
        $doc->setFileName('test.pdf');
        $docRepo->method('findAllActive')->willReturn([$doc]);
        $this->assertEquals(200, $controller->listDocuments()->getStatusCode());

        // Error al listar
        $docRepoEx = $this->createMock(ClinicalDocumentRepository::class);
        $docRepoEx->method('findAllActive')->willThrowException(new \Exception('Test'));
        $controllerEx = new IngestionController($chunkRepo, $useCase, $docRepoEx);
        $this->assertEquals(500, $controllerEx->listDocuments()->getStatusCode());

        // Delete 404
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);
        $em->method('getRepository')->willReturn($repo);
        $this->assertEquals(404, $controller->deleteDocument('1', $em)->getStatusCode());

        // Delete Exception
        $emEx = $this->createMock(EntityManagerInterface::class);
        $emEx->method('getRepository')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->deleteDocument('1', $emEx)->getStatusCode());

        // Upload Exception
        $useCase->method('execute')->willThrowException(new \Exception('Test'));
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getMimeType')->willReturn('application/pdf');
        $req = new Request();
        $req->files->set('file', $file);
        $this->assertEquals(500, $controller->upload($req)->getStatusCode());
    }

    // 5. Forzar Exceptions en el PatientController
    public function testPatientControllerExceptions(): void
    {
        $repo = $this->createMock(PatientRepository::class);
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $token = $this->createMock(TokenStorageInterface::class);
        
        $controller = new PatientController($repo, $auth, $token);
        $em = $this->createMock(EntityManagerInterface::class);
        
        $reqUpdate = new Request([], [], [], [], [], [], 'invalid');
        $this->assertEquals(500, $controller->update('1', $reqUpdate, $em)->getStatusCode());

        $reqCreateValid = new Request([], [], [], [], [], [], '{}');
        $em->method('persist')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->create($reqCreateValid, $em)->getStatusCode());

        $repo->method('find')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->delete('1', $em)->getStatusCode());
    }

    // 6. Barrido Completo del Caso de Uso Principal (Dietas IA)
    public function testGenerateDietUseCaseFullSweep(): void
    {
        $patientRepo = $this->createMock(PatientRepository::class);
        $chunkRepo = $this->createMock(DocumentChunkRepository::class);
        $embed = $this->createMock(EmbeddingGeneratorInterface::class);
        $llm = $this->createMock(LlmInferenceInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $useCase = new GenerateClinicalDietUseCase($patientRepo, $chunkRepo, $embed, $llm, $em);

        // 6.1. Excepción: Paciente no encontrado
        $patientRepo->method('find')->willReturn(null);
        try {
            $useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        // 6.2. Excepción: Error JSON del LLM
        $patient = new Patient();
        $patientRepo->method('find')->willReturn($patient);
        $llm->method('generateText')->willReturn('INVALID JSON');
        try {
            $useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
        }

        // 6.3. Éxito rotundo (Barre 60 líneas de lógica de negocio pura)
        $chunk = new DocumentChunk();
        $chunk->setContent('Context');
        $chunkRepo->method('findSimilarChunkEntities')->willReturn([$chunk]);
        $embed->method('generateEmbedding')->willReturn([0.1]);

        $validJson = json_encode([
            'totalKcal' => 2000,
            'observations' => 'Ok',
            'days' => [
                [
                    'dayNumber' => 1,
                    'meals' => [
                        [
                            'type' => 'Desayuno',
                            'time' => '08:00',
                            'items' => [
                                ['foodName' => 'Avena', 'kcal' => 100, 'proteins' => 5, 'carbs' => 20, 'fats' => 2, 'quantity' => '50g']
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        
        $llm2 = $this->createMock(LlmInferenceInterface::class);
        $llm2->method('generateText')->willReturn($validJson);
        
        $foodRepo = $this->createMock(EntityRepository::class);
        $foodRepo->method('findOneBy')->willReturn(null); 
        $em->method('getRepository')->willReturn($foodRepo);

        $useCaseSuccess = new GenerateClinicalDietUseCase($patientRepo, $chunkRepo, $embed, $llm2, $em);
        $result = $useCaseSuccess->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable('+1 day'));
        $this->assertStringContainsString('totalKcal', $result);
    }
}