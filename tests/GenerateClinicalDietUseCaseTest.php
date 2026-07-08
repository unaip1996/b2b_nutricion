<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use App\Infrastructure\Repository\DocumentChunkRepository;
use App\Infrastructure\Repository\PatientRepository;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\DocumentChunk;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

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
        $patient = $this->createMock(Patient::class);
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

        $chunkMock = $this->createMock(DocumentChunk::class);
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
}