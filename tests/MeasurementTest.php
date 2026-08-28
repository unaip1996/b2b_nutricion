<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\Measurement;
use App\Infrastructure\Entity\Patient;
use PHPUnit\Framework\TestCase;

class MeasurementTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $measurement = new Measurement();
        $patient = $this->createStub(Patient::class);
        $takenAt = new \DateTimeImmutable('2026-08-01');
        $deletedAt = new \DateTimeImmutable('2026-08-02');

        $measurement->setPatient($patient);
        $measurement->setWeight(80.5);
        $measurement->setHeight(180.0);
        $measurement->setBodyFatPercentage(15.5);
        $measurement->setMuscleMass(40.0);
        $measurement->setWaistCircumference(85.0);
        $measurement->setTakenAt($takenAt);
        $measurement->setDeletedAt($deletedAt);

        $this->assertSame($patient, $measurement->getPatient());
        $this->assertSame(80.5, $measurement->getWeight());
        $this->assertSame(180.0, $measurement->getHeight());
        $this->assertSame(15.5, $measurement->getBodyFatPercentage());
        $this->assertSame(40.0, $measurement->getMuscleMass());
        $this->assertSame(85.0, $measurement->getWaistCircumference());
        $this->assertSame($takenAt, $measurement->getTakenAt());
        $this->assertSame($deletedAt, $measurement->getDeletedAt());
        $this->assertNull($measurement->getId());
    }
}