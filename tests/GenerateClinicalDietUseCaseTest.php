<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use App\Infrastructure\Repository\DocumentChunkRepository;
use App\Infrastructure\Repository\PatientRepository;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\Allergy;
use App\Infrastructure\Entity\FoodItem;
use App\Infrastructure\Entity\DocumentChunk;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class GenerateClinicalDietUseCaseTest extends TestCase
{
    private PatientRepository $patientRepository;
    private DocumentChunkRepository $documentChunkRepository;
    private EmbeddingGeneratorInterface $embeddingGenerator;
    private LlmInferenceInterface $llmInference;
    private EntityManagerInterface $entityManager;
    private GenerateClinicalDietUseCase $useCase;

    protected function setUp(): void
    {
        $this->patientRepository = $this->createMock(PatientRepository::class);
        $this->documentChunkRepository = $this->createMock(DocumentChunkRepository::class);
        $this->embeddingGenerator = $this->createMock(EmbeddingGeneratorInterface::class);
        $this->llmInference = $this->createMock(LlmInferenceInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->useCase = new GenerateClinicalDietUseCase(
            $this->patientRepository,
            $this->documentChunkRepository,
            $this->embeddingGenerator,
            $this->llmInference,
            $this->entityManager
        );
    }

    public function testExecuteBuildsCorrectPromptAndReturnsDietContent(): void
    {
        $patientId = 'patient-uuid-123';
        $query = 'Dieta alta en proteínas';
        $kcal = 2000;
        $startDate = new \DateTimeImmutable();
        $endDate = new \DateTimeImmutable('+7 days');

        // 1. Configurar Mock de Paciente
        $patient = $this->createStub(Patient::class);
        $patient->method('getAllergies')->willReturn(new ArrayCollection());
        $patient->method('getPathologies')->willReturn('Ninguna registrada');
        $patient->method('getMeasurements')->willReturn(new ArrayCollection());

        $this->patientRepository->expects($this->once())
            ->method('find')
            ->with($patientId)
            ->willReturn($patient);

        // 2. Configurar Mocks del RAG
        $this->embeddingGenerator->expects($this->once())
            ->method('generateEmbedding')
            ->with($query)
            ->willReturn([0.1, 0.2, 0.3]);

        $chunkMock = $this->createStub(DocumentChunk::class);
        $chunkMock->method('getContent')->willReturn('Guía médica sobre proteínas.');

        $this->documentChunkRepository->expects($this->once())
            ->method('findSimilarChunkEntities')
            ->willReturn([$chunkMock]);

        // 3. Configurar Mock del LLM
        $expectedLlmJson = '{"totalKcal": 2000, "observations": "Dieta alta en proteínas", "days": []}';
        $this->llmInference->expects($this->once())
            ->method('generateText')
            ->willReturn($expectedLlmJson);

        // 4. Ejecutar el Caso de Uso
        $result = $this->useCase->execute($patientId, $query, $kcal, $startDate, $endDate);

        // 5. Verificar Resultado
        $this->assertSame($expectedLlmJson, $result);
    }

    public function testExecuteThrowsExceptionWhenPatientNotFound(): void
    {
        $this->patientRepository->method('find')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function testExecuteThrowsExceptionOnInvalidJsonFromLlm(): void
    {
        $this->patientRepository->method('find')->willReturn(new Patient());
        $this->llmInference->method('generateText')->willReturn('ESTO NO ES UN JSON');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La IA no devolvió un JSON válido');
        $this->useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function testExecuteThrowsExceptionOnMissingDaysKeyInLlmResponse(): void
    {
        $this->patientRepository->method('find')->willReturn(new Patient());

        // JSON válido pero sin la clave 'days'
        $invalidJson = json_encode([
            'totalKcal' => 2000,
            'observations' => 'Dieta sin días.',
        ]);
        $this->llmInference->method('generateText')->willReturn($invalidJson);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La IA no devolvió una estructura de días válida.');

        $this->useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function testExecuteHandlesInvalidMealTimeGracefully(): void
    {
        $patient = new Patient();
        $this->patientRepository->method('find')->willReturn($patient);

        $chunk = new DocumentChunk();
        $chunk->setContent('Contexto Médico');
        $this->documentChunkRepository->method('findSimilarChunkEntities')->willReturn([$chunk]);

        $jsonWithInvalidTime = json_encode([
            'days' => [
                [
                    'dayNumber' => 1,
                    'meals' => [
                        [
                            'type' => 'Desayuno',
                            'time' => 'hora-invalida', // Formato de hora incorrecto
                            'items' => []
                        ]
                    ]
                ]
            ]
        ]);
        $this->llmInference->method('generateText')->willReturn($jsonWithInvalidTime);

        // Se espera que el EntityManager persista el plan de dieta aunque la hora sea nula.
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
        $this->assertSame($jsonWithInvalidTime, $result);
    }

    public function testExecuteCreatesNewFoodItemWhenNotFoundInDatabase(): void
    {
        $patient = new Patient();
        $this->patientRepository->method('find')->willReturn($patient);

        $chunk = new DocumentChunk();
        $chunk->setContent('Contexto Médico');
        $this->documentChunkRepository->method('findSimilarChunkEntities')->willReturn([$chunk]);

        $jsonWithNewFood = json_encode([
            'days' => [[
                'dayNumber' => 1,
                'meals' => [[
                    'type' => 'Comida',
                    'time' => '14:00',
                    'items' => [[
                        'foodName' => 'Alimento Nuevo Inexistente',
                        'quantity' => '100 g',
                        'kcal' => 500
                    ]]
                ]]
            ]]
        ]);
        $this->llmInference->method('generateText')->willReturn($jsonWithNewFood);

        // Mock del repositorio de FoodItem para que devuelva null
        $foodRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $foodRepo->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($foodRepo);

        // Se espera que se persista el DietaryPlan y el nuevo FoodItem
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->useCase->execute('1', 'q', 2000, new \DateTimeImmutable(), new \DateTimeImmutable());
    }
}