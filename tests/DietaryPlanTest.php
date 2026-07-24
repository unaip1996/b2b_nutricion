<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\DietDay;
use App\Infrastructure\Entity\DocumentChunk;
use App\Infrastructure\Entity\Patient;
use PHPUnit\Framework\TestCase;

class DietaryPlanTest extends TestCase
{
    public function testDietaryPlanGettersAndSetters(): void
    {
        $plan = new DietaryPlan();
        $patient = $this->createStub(Patient::class);
        $startDate = new \DateTimeImmutable('2026-08-01');
        $endDate = new \DateTimeImmutable('2026-08-07');
        $deletedAt = new \DateTimeImmutable('2026-08-10');

        // Probamos todos los setters de la entidad
        $plan->setPatient($patient);
        $plan->setName('Plan de Prueba');
        $plan->setKcal(2500);
        $plan->setObservations('Observaciones de prueba');
        $plan->setStartDate($startDate);
        $plan->setEndDate($endDate);
        $plan->setDeletedAt($deletedAt);

        // Verificamos con los getters
        $this->assertSame($patient, $plan->getPatient());
        $this->assertSame('Plan de Prueba', $plan->getName());
        $this->assertSame(2500, $plan->getKcal());
        $this->assertSame('Observaciones de prueba', $plan->getObservations());
        $this->assertSame($startDate, $plan->getStartDate());
        $this->assertSame($endDate, $plan->getEndDate());
        $this->assertSame($deletedAt, $plan->getDeletedAt());
        $this->assertNull($plan->getId());
    }

    public function testDietaryPlanCollections(): void
    {
        $plan = new DietaryPlan();
        $chunk = new DocumentChunk();
        $dietDay = new DietDay();

        // Probar ArrayCollection de DocumentChunks
        $plan->addDocumentChunk($chunk);
        $this->assertTrue($plan->getDocumentChunks()->contains($chunk));
        
        $plan->removeDocumentChunk($chunk);
        $this->assertFalse($plan->getDocumentChunks()->contains($chunk));

        // Probar ArrayCollection de DietDays
        $plan->addDietDay($dietDay);
        $this->assertTrue($plan->getDietDays()->contains($dietDay));
        $this->assertSame($plan, $dietDay->getDietaryPlan());

        $plan->removeDietDay($dietDay);
        $this->assertFalse($plan->getDietDays()->contains($dietDay));
    }

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