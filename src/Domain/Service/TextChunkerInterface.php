<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface TextChunkerInterface
{
    /**
     * Divide el texto extraído en fragmentos más pequeños.
     *
     * @return string[]
     */
    public function chunkText(string $text): array;
}