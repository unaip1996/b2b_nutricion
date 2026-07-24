<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\Allergy;
use App\Infrastructure\Entity\Patient;
use PHPUnit\Framework\TestCase;

class AllergyTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $allergy = new Allergy();
        $deletedAt = new \DateTimeImmutable();

        $allergy->setName('Gluten');
        $allergy->setDeletedAt($deletedAt);

        $this->assertSame('Gluten', $allergy->getName());
        $this->assertSame($deletedAt, $allergy->getDeletedAt());
        $this->assertNull($allergy->getId());
    }

    public function testPatientCollection(): void
    {
        $allergy = new Allergy();
        $patient = $this->createStub(Patient::class);

        // Añadir
        $allergy->addPatient($patient);
        $this->assertTrue($allergy->getPatients()->contains($patient));

        // Eliminar
        $allergy->removePatient($patient);
        $this->assertFalse($allergy->getPatients()->contains($patient));
    }
}