<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\Allergy;
use App\Infrastructure\Entity\Measurement;
use PHPUnit\Framework\TestCase;

class PatientTest extends TestCase
{
    public function testPacientePuedeRegistrarMultiplesMedicionesParaEvolucion(): void
    {
        $patient = new Patient();
        
        $measurement1 = new Measurement();
        // Usamos los campos exactos de tu diagrama ER
        $measurement1->setWeight(81.5);
        $measurement1->setMuscleMass(40.2);
        
        $measurement2 = new Measurement();
        $measurement2->setWeight(80.0);
        $measurement2->setMuscleMass(41.0);

        // Simulamos la colección OneToMany
        $patient->addMeasurement($measurement1);
        $patient->addMeasurement($measurement2);

        $this->assertCount(2, $patient->getMeasurements());
        // Comprobamos que el histórico persiste correctamente
        $this->assertSame(81.5, $patient->getMeasurements()->first()->getWeight());
    }

    public function testPacienteDetectaRestriccionAlergenicaCritica(): void
    {
        $patient = new Patient();
        
        $allergy = new Allergy();
        $allergy->setName('Gluten');
        
        $patient->addAllergy($allergy);

        $this->assertCount(1, $patient->getAllergies());
        $this->assertSame('Gluten', $patient->getAllergies()->first()->getName());
    }

    public function testEstadoActivoDelPaciente(): void
    {
        $patient = new Patient();
        $patient->setActiveStatus(true);
        
        $this->assertTrue($patient->isActiveStatus());
    }
}