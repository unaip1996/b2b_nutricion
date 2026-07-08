<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Service\PdfExtractorInterface;
use App\Domain\Service\TextChunkerInterface;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Entity\DocumentChunk;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class IngestClinicalDocumentUseCase
{
    public function __construct(
        private PdfExtractorInterface $pdfExtractor,
        private TextChunkerInterface $textChunker,
        private EmbeddingGeneratorInterface $embeddingGenerator,
        private EntityManagerInterface $entityManager
    ) {}

    public function execute(UploadedFile $file): array
    {
        // 1. Extraer el texto del PDF
        $text = $this->pdfExtractor->extractText($file->getPathname());
        
        if (empty(trim($text))) {
            throw new \RuntimeException('El documento está vacío o es una imagen escaneada sin texto legible.');
        }

        // 2. Crear la entidad padre del documento
        $document = new ClinicalDocument();
        $document->setFileName($file->getClientOriginalName());
        
        // 3. Dividir el texto en fragmentos (Chunks)
        $chunksText = $this->textChunker->chunkText($text);

        // 4. Generar vectores y vincular al documento padre
        foreach ($chunksText as $index => $chunkText) {
            // Generar el vector matemático de 1536 dimensiones con OpenAI
            $embedding = $this->embeddingGenerator->generateEmbedding($chunkText);

            // Crear el fragmento y vincularlo
            $chunk = new DocumentChunk();
            $chunk->setContent($chunkText);
            
            $chunk->setEmbedding(json_encode($embedding)); 
            
            $chunk->setMetadata([
                'chunk_index' => $index,
                'total_chunks' => count($chunksText)
            ]);
            
            // LA MAGIA: Vinculamos el fragmento a su padre
            $document->addChunk($chunk);
        }

        // 5. Guardar todo en PostgreSQL. Al hacer persist del padre, Doctrine guarda los chunks por cascada.
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return [
            'status' => 'success',
            'document_id' => $document->getId()->toString(),
            'chunks_processed' => count($chunksText)
        ];
    }
}