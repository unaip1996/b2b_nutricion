<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\TextChunkerInterface;

class BasicTextChunker implements TextChunkerInterface
{
    private const CHUNK_SIZE = 1000; // Aproximadamente 1000 caracteres por fragmento
    private const OVERLAP = 200;     // Solapamiento para no cortar frases por la mitad

    public function chunkText(string $text): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        // Si el texto es muy corto, lo devolvemos tal cual
        if ($length <= self::CHUNK_SIZE) {
            return [$text];
        }

        while ($start < $length) {
            // Extraer el fragmento
            $chunk = mb_substr($text, $start, self::CHUNK_SIZE);
            $chunks[] = $chunk;

            // Avanzar restando el solapamiento para mantener el contexto entre fragmentos
            $start += (self::CHUNK_SIZE - self::OVERLAP);
        }

        return $chunks;
    }
}