<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\DocumentChunk;
use PHPUnit\Framework\TestCase;

class DietaryPlanEntityTest extends TestCase
{
    /**
     * Este test unitario valida que la entidad DietaryPlan puede mantener una
     * relación Many-to-Many con los fragmentos de conocimiento que la generaron (trazabilidad del RAG).
     */
    public function testDietaryPlanMantieneTrazabilidadDocumentalRag(): void
    {
        $dietPlan = new DietaryPlan();
        
        // Creamos los fragmentos vectoriales simulados
        $chunk1 = new DocumentChunk();
        $chunk1->setContent('Directriz OMS sobre ingesta proteica.');
        
        $chunk2 = new DocumentChunk();
        $chunk2->setContent('Estudio de superávit calórico.');

        // Vinculamos la dieta con el conocimiento (ManyToMany)
        $dietPlan->addDocumentChunk($chunk1);
        $dietPlan->addDocumentChunk($chunk2);

        $this->assertCount(2, $dietPlan->getDocumentChunks());
    }
}