<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface EmbeddingGeneratorInterface
{
    /**
     * Genera un vector matemático a partir de una cadena de texto.
     *
     * @return float[]
     */
    public function generateEmbedding(string $text): array;
}