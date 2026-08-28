<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\PdfExtractorInterface;
use App\Domain\Service\TextChunkerInterface;
use App\Infrastructure\Entity\ClinicalDocument;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AllowMockObjectsWithoutExpectations]
class IngestClinicalDocumentUseCaseTest extends TestCase
{
    private PdfExtractorInterface $pdfExtractor;
    private TextChunkerInterface $textChunker;
    private EmbeddingGeneratorInterface $embeddingGenerator;
    private EntityManagerInterface $entityManager;
    private IngestClinicalDocumentUseCase $useCase;

    protected function setUp(): void
    {
        $this->pdfExtractor = $this->createMock(PdfExtractorInterface::class);
        $this->textChunker = $this->createMock(TextChunkerInterface::class);
        $this->embeddingGenerator = $this->createMock(EmbeddingGeneratorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->useCase = new IngestClinicalDocumentUseCase(
            $this->pdfExtractor,
            $this->textChunker,
            $this->embeddingGenerator,
            $this->entityManager
        );
    }

    public function testExecuteSuccessfully(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($filePath, 'dummy content');
        $uploadedFile = new UploadedFile($filePath, 'test.pdf', test: true);

        $this->pdfExtractor->expects($this->once())
            ->method('extractText')
            ->willReturn('Clinical text content.');

        $this->textChunker->expects($this->once())
            ->method('chunkText')
            ->with('Clinical text content.')
            ->willReturn(['chunk 1', 'chunk 2']);

        $this->embeddingGenerator->expects($this->exactly(2))
            ->method('generateEmbedding')
            ->willReturn([0.1, 0.2, 0.3]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ClinicalDocument::class))
            ->willReturnCallback(function (ClinicalDocument $doc): void {
                // Simulate Doctrine setting the ID on persist/flush
                \Closure::bind(fn () => $this->id = \Symfony\Component\Uid\Uuid::v4(), $doc, ClinicalDocument::class)();
            });
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->useCase->execute($uploadedFile);

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('document_id', $result);
        $this->assertSame(2, $result['chunks_processed']);
    }

    public function testExecuteThrowsExceptionForEmptyDocument(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El documento está vacío o es una imagen escaneada sin texto legible.');

        $uploadedFile = new UploadedFile(tempnam(sys_get_temp_dir(), 'upl'), 'empty.pdf', test: true);

        $this->pdfExtractor->expects($this->once())
            ->method('extractText')
            ->willReturn('   '); // Empty or whitespace content

        $this->useCase->execute($uploadedFile);
    }
}