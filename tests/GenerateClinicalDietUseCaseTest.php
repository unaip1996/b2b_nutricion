<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Domain\Repository\DocumentChunkRepositoryInterface;
use App\Domain\Service\RagEngineInterface;
use App\Infrastructure\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class GenerateClinicalDietUseCaseTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DocumentChunkRepositoryInterface $chunkRepository;
    private RagEngineInterface $ragEngine;
    private GenerateClinicalDietUseCase $useCase;

    protected function setUp(): void
    {
        // Creamos Mocks para las dependencias (puertos de la arquitectura)
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->chunkRepository = $this->createMock(DocumentChunkRepositoryInterface::class);
        $this->ragEngine = $this->createMock(RagEngineInterface::class);

        // Asumimos que el caso de uso depende de EntityManagerInterface porque no parece haber un PatientRepository personalizado.
        $this->useCase = new GenerateClinicalDietUseCase(
            $this->entityManager,
            $this->chunkRepository,
            $this->ragEngine
        );
    }

    public function testExecuteBuildsCorrectPromptAndReturnsRagResult(): void
    {
        $patientId = 'patient-uuid-123';
        $query = 'Dieta de definición para varón de 30 años';
        $kcal = 2200;

        // 1. Configuramos los Mocks
        $patient = new Patient();
        $patient->setName('Test Patient');
        // ... configurar más datos del paciente

        $patientRepositoryMock = $this->createMock(EntityRepository::class);
        $patientRepositoryMock->expects($this->once())->method('find')->with($patientId)->willReturn($patient);
        $this->entityManager->expects($this->once())->method('getRepository')->with(Patient::class)->willReturn($patientRepositoryMock);

        $this->chunkRepository->method('findSimilarText')->willReturn(['Contexto de estudio 1.', 'Contexto de estudio 2.']);

        $expectedRagResult = '{"plan": "generado"}';
        $this->ragEngine->expects($this->once())
            ->method('generateDiet')
            // Verificamos que el prompt final que se envía al LLM contiene las piezas clave:
            // los datos del paciente y el contexto recuperado de la base de datos vectorial.
            // El método 'with' espera un solo argumento de tipo constraint si el método mockeado tiene un solo parámetro.
            // Usamos logicalAnd para combinar múltiples aserciones sobre ese único argumento.
            ->with($this->logicalAnd($this->stringContains('Test Patient'), $this->stringContains('Contexto de estudio 1')))
            ->willReturn($expectedRagResult);

        // 2. Ejecutamos el Caso de Uso
        $result = $this->useCase->execute($patientId, $query, $kcal, new \DateTimeImmutable(), new \DateTimeImmutable());

        // 3. Verificamos el resultado
        $this->assertSame($expectedRagResult, $result);
    }
}