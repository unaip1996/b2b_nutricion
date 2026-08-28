<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Infrastructure\Service\BasicTextChunker;
use PHPUnit\Framework\TestCase;

class BasicTextChunkerTest extends TestCase
{
    private BasicTextChunker $chunker;

    protected function setUp(): void
    {
        $this->chunker = new BasicTextChunker();
    }

    public function testChunkTextWithShortTextReturnsSingleChunk(): void
    {
        $text = 'Este es un texto corto que no necesita ser dividido.';
        $chunks = $this->chunker->chunkText($text);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]);
    }

    public function testChunkTextWithLongTextCreatesOverlappingChunks(): void
    {
        // Basado en las constantes de la clase: CHUNK_SIZE = 1200, OVERLAP = 200
        $longText = str_repeat('a', 2000);
        $chunks = $this->chunker->chunkText($longText);

        $this->assertCount(2, $chunks, 'Debería crear 2 chunks para un texto de 2000 caracteres.');
        
        $this->assertSame(1200, mb_strlen($chunks[0]));
        
        // El segundo chunk debe empezar en la posición 1000 (1200 - 200) y contener los 1000 caracteres restantes.
        $this->assertSame(1000, mb_strlen($chunks[1]));
        $this->assertSame(mb_substr($longText, 1000), $chunks[1]);
    }
}