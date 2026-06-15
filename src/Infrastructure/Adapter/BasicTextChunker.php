<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter; 

use App\Domain\Service\TextChunkerInterface;

class BasicTextChunker implements TextChunkerInterface
{
    /**
     * Implementación básica para trocear texto con solapamiento (overlap).
     */
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        if (empty($text)) {
            return [];
        }

        $chunks = [];
        $textLength = mb_strlen($text);
        $startIndex = 0;

        while ($startIndex < $textLength) {
            $chunks[] = mb_substr($text, $startIndex, $chunkSize);
            
            $startIndex += ($chunkSize - $overlap);
            
            if ($startIndex >= $textLength || $chunkSize <= $overlap) {
                break;
            }
        }

        return $chunks;
    }
}