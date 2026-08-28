<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\DocumentChunk;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\ClinicalDocument;
use PHPUnit\Framework\TestCase;

class DocumentChunkTest extends TestCase
{
    public function testDocumentChunkGettersAndSetters(): void
    {
        $chunk = new DocumentChunk();
        $document = $this->createStub(ClinicalDocument::class);
        $deletedAt = new \DateTimeImmutable('2026-08-10');
        
        // Probamos todos los setters
        $chunk->setContent('Contenido médico sobre hipertrofia.');
        $chunk->setEmbedding('[0.1, 0.2, 0.3]'); // En BD es vector, en PHP lo tratas como string
        $chunk->setMetadata(['page' => 1, 'source' => 'pdf']);
        $chunk->setDeletedAt($deletedAt);
        $chunk->setClinicalDocument($document);

        // Verificamos con getters
        $this->assertSame('Contenido médico sobre hipertrofia.', $chunk->getContent());
        $this->assertSame('[0.1, 0.2, 0.3]', $chunk->getEmbedding());
        $this->assertSame(['page' => 1, 'source' => 'pdf'], $chunk->getMetadata());
        $this->assertSame($deletedAt, $chunk->getDeletedAt());
        $this->assertSame($document, $chunk->getClinicalDocument());
        $this->assertNull($chunk->getId());
    }

    public function testDocumentChunkCollections(): void
    {
        $chunk = new DocumentChunk();
        $plan = new DietaryPlan();

        // Probar añadir y quitar planes dietéticos
        $chunk->addDietaryPlan($plan);
        $this->assertTrue($chunk->getDietaryPlans()->contains($plan));

        $chunk->removeDietaryPlan($plan);
        $this->assertFalse($chunk->getDietaryPlans()->contains($plan));
    }
}