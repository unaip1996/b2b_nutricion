<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Service\PdfExtractorInterface;
use App\Domain\Service\TextChunkerInterface;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Repository\DocumentChunkRepositoryInterface;
use App\Infrastructure\Entity\DocumentChunk;


readonly class IngestClinicalDocumentUseCase
{
    public function __construct(
        private PdfExtractorInterface $pdfExtractor,
        private TextChunkerInterface $textChunker,
        private EmbeddingGeneratorInterface $embeddingGenerator,
        private DocumentChunkRepositoryInterface $documentChunkRepository,
    ) {
    }

    public function execute(string $pdfPath): void
    {
        $text = $this->pdfExtractor->extractText($pdfPath);
        $chunks = $this->textChunker->chunkText($text);

        foreach ($chunks as $chunkContent) {
            $embedding = $this->embeddingGenerator->generate($chunkContent);

            $documentChunk = new DocumentChunk();
            $documentChunk->setContent($chunkContent);
            $documentChunk->setEmbedding(json_encode($embedding, JSON_THROW_ON_ERROR));

            $this->documentChunkRepository->save($documentChunk);
        }
    }
}