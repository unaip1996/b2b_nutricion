<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Infrastructure\Entity\DocumentChunk;

interface DocumentChunkRepositoryInterface
{
    public function save(DocumentChunk $chunk): void;
}