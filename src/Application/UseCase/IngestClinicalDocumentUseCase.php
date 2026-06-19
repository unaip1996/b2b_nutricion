<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Service\PdfExtractorInterface;
use App\Domain\Service\TextChunkerInterface;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\DocumentChunk;
use Doctrine\ORM\EntityManagerInterface;

readonly class IngestClinicalDocumentUseCase
{
    public function __construct(
        private PdfExtractorInterface $pdfExtractor,
        private TextChunkerInterface $textChunker,
        private EmbeddingGeneratorInterface $embeddingGenerator,
        private EntityManagerInterface $em
    ) {}

    public function execute(Patient $patient, string $filePath, string $originalFileName): void
    {
        // 1. Extracción: Leer el PDF
        $text = $this->pdfExtractor->extractText($filePath);

        if (empty(trim($text))) {
            throw new \RuntimeException('No se pudo extraer texto del PDF (Asegúrate de que no es una imagen escaneada sin OCR).');
        }

        // 2. Chunking: Dividir en fragmentos semánticos
        $chunks = $this->textChunker->chunkText($text);

        // 3. Embedding: Vectorizar y persistir
        foreach ($chunks as $index => $chunkText) {
            // Generamos el array de embeddings
            $vectorArray = $this->embeddingGenerator->generateEmbedding($chunkText);

            $documentChunk = new DocumentChunk();
            $documentChunk->setContent($chunkText);
            
            // La entidad espera un string para el vector. 
            // json_encode formatea el array [0.1, 0.2...] exactamente como PostgreSQL espera para pgvector.
            $documentChunk->setEmbedding(json_encode($vectorArray));
            
            // Guardamos todo el contexto relacional en la columna metadata
            $documentChunk->setMetadata([
                'patient_id'  => (string) $patient->getId(),
                'file_name'   => $originalFileName,
                'chunk_index' => $index,
                'ingested_at' => (new \DateTimeImmutable())->format('c'),
            ]);

            $this->em->persist($documentChunk);
        }

        $this->em->flush();
    }
}