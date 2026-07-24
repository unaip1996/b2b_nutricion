<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\PdfExtractorInterface;
use App\Domain\Service\TextChunkerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use App\Infrastructure\Entity\ClinicalDocument;

class IngestClinicalDocumentUseCaseTest extends TestCase
{
    public function testExecuteSuccessfullyProcessesDocument(): void
    {
        $pdfExtractor = $this->createMock(PdfExtractorInterface::class);
        $textChunker = $this->createMock(TextChunkerInterface::class);
        $embeddingGenerator = $this->createMock(EmbeddingGeneratorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $useCase = new IngestClinicalDocumentUseCase(
            $pdfExtractor,
            $textChunker,
            $embeddingGenerator,
            $entityManager
        );

        $file = $this->createStub(UploadedFile::class);
        $file->method('getPathname')->willReturn('/tmp/dummy.pdf');
        $file->method('getClientOriginalName')->willReturn('guia_clinica.pdf');

        $pdfExtractor->expects($this->once())->method('extractText')->willReturn('Texto de prueba clínica');
        $textChunker->expects($this->once())->method('chunkText')->willReturn(['Texto de prueba clínica']);
        $embeddingGenerator->expects($this->once())->method('generateEmbedding')->willReturn([0.1, 0.2, 0.3]);
        
        // LA MAGIA: Interceptamos el persist y usamos Reflection para asignarle el ID al objeto real instanciado
        $entityManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($entity) {
                if ($entity instanceof ClinicalDocument) {
                    $reflection = new \ReflectionClass($entity);
                    $property = $reflection->getProperty('id');
                    $property->setValue($entity, Uuid::fromString('00000000-0000-0000-0000-000000000000'));
                }
            });
            
        $entityManager->expects($this->once())->method('flush');

        $result = $useCase->execute($file);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['chunks_processed']);
        $this->assertSame('00000000-0000-0000-0000-000000000000', $result['document_id']);
    }
}