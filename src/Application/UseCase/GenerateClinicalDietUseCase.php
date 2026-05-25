<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\DocumentChunkRepositoryInterface;
use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use App\Infrastructure\Entity\DocumentChunk; // O la ruta correcta donde tengas la entidad

readonly class GenerateClinicalDietUseCase
{
    public function __construct(
        private EmbeddingGeneratorInterface $embeddingGenerator,
        private DocumentChunkRepositoryInterface $chunkRepository,
        private LlmInferenceInterface $llmInference,
    ) {
    }

    public function execute(string $clinicalQuery): string
    {
        // 1. Vectorizar la consulta
        $embedding = $this->embeddingGenerator->generate($clinicalQuery);

        // 2. Buscar en base de datos (Sabemos que devuelve DocumentChunk[])
        /** @var DocumentChunk[] $similarChunks */
        $similarChunks = $this->chunkRepository->findSimilar($embedding);

        // 3. Extraer el contexto de forma funcional (confiando en tu contrato)
        $context = array_map(
            fn(DocumentChunk $chunk) => $chunk->getContent(), 
            $similarChunks
        );

        // 4. Inferencia con RAG
        return $this->llmInference->generateResponse($clinicalQuery, $context);
    }
}