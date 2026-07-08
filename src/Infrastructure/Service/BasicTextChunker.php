<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\TextChunkerInterface;

/**
 * Servicio de Infraestructura para dividir el texto en fragmentos (Chunks)
 * optimizado para embeddings vectoriales (OpenAI).
 */
readonly class BasicTextChunker implements TextChunkerInterface
{
    // Tamaño ideal para text-embedding-3-small y buen contexto (aprox. 250-300 tokens)
    private const CHUNK_SIZE = 1200; 
    
    // Solapamiento para no perder el contexto si cortamos a mitad de un párrafo
    private const OVERLAP = 200;     

    public function chunkText(string $text): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        // Si el texto completo es más pequeño que un chunk, no hay nada que dividir
        if ($length <= self::CHUNK_SIZE) {
            return [$text];
        }

        while ($start < $length) {
            // Extraer el fragmento del tamaño máximo permitido
            $chunk = mb_substr($text, $start, self::CHUNK_SIZE);
            
            // Lo añadimos a nuestra lista de fragmentos
            $chunks[] = $chunk;

            // Avanzamos el cursor, pero restando el solapamiento para repetir el final 
            // de este texto al principio del siguiente chunk.
            $start += (self::CHUNK_SIZE - self::OVERLAP);
        }

        return $chunks;
    }
}