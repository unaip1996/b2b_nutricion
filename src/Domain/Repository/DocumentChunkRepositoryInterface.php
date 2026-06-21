<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Infrastructure\Entity\DocumentChunk;

interface DocumentChunkRepositoryInterface
{
    public function save(DocumentChunk $chunk): void;

    /**
     * Recupera los fragmentos de texto más similares semánticamente.
     * @param float[] $embedding
     * @return DocumentChunk[]
     */
    public function findSimilar(array $embedding, int $limit = 5): array;
}