<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\TextChunkerInterface;

/**
 * Servicio de Infraestructura básico para dividir el texto en fragmentos.
 */
readonly class BasicTextChunker implements TextChunkerInterface
{
    public function chunkText(string $text): array
    {
        $paragraphs = explode("\n\n", $text);
        $chunks = [];
        $currentChunk = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            
            if ($paragraph === '') {
                continue;
            }

            if (strlen($currentChunk) + strlen($paragraph) > 1000 && $currentChunk !== '') {
                $chunks[] = trim($currentChunk);
                $currentChunk = '';
            }

            $currentChunk .= $paragraph . "\n\n";
        }

        if ($currentChunk !== '') {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}