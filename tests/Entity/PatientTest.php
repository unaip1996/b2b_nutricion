<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\Allergy;
use App\Infrastructure\Entity\Measurement;
use App\Infrastructure\Entity\DietaryPlan;
use PHPUnit\Framework\TestCase;

class PatientTest extends TestCase
{
    public function testPacientePuedeRegistrarMultiplesMedicionesParaEvolucion(): void
    {
        $patient = new Patient();
        
        $measurement1 = new Measurement();
        $measurement1->setWeight(81.5);
        $measurement1->setMuscleMass(40.2);
        
        $measurement2 = new Measurement();
        $measurement2->setWeight(80.0);
        $measurement2->setMuscleMass(41.0);

        $patient->addMeasurement($measurement1);
        $patient->addMeasurement($measurement2);

        $this->assertCount(2, $patient->getMeasurements());
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

    public function testPatientRemaining(): void
    {
        $patient = new Patient();
        $patient->setEmail('correo@test.com');
        $this->assertSame('correo@test.com', $patient->getEmail());
        
        $patient->setNutritionalGoal('Bajar peso');
        $this->assertSame('Bajar peso', $patient->getNutritionalGoal());
        
        $patient->setActiveStatus(false);
        $this->assertFalse($patient->isActiveStatus());
        
        $date = new \DateTimeImmutable();
        $patient->setDeletedAt($date);
        $this->assertSame($date, $patient->getDeletedAt());
    }

    public function testPatientRemainingMethods(): void
    {
        $patient = new Patient();
        $measurement = new Measurement();
        $allergy = new Allergy();

        $patient->addMeasurement($measurement);
        $this->assertTrue($patient->getMeasurements()->contains($measurement));

        $patient->removeMeasurement($measurement);
        $this->assertFalse($patient->getMeasurements()->contains($measurement));
        
        $patient->addAllergy($allergy);
        $this->assertTrue($patient->getAllergies()->contains($allergy));

        $patient->removeAllergy($allergy);
        $this->assertFalse($patient->getAllergies()->contains($allergy));

        $patient->setPathologies('Hipertensión');
        $this->assertSame('Hipertensión', $patient->getPathologies());

        $patient->setClinicalNotes('Notas clínicas');
        $this->assertSame('Notas clínicas', $patient->getClinicalNotes());
        
        $patient->setPhone('+34 600 000 000');
        $this->assertSame('+34 600 000 000', $patient->getPhone());

        $date = new \DateTimeImmutable();
        $patient->setDeletedAt($date);
        $this->assertSame($date, $patient->getDeletedAt());

        $patient->setActiveStatus(true);
        $this->assertTrue($patient->isActiveStatus());

        $patient->setNutritionalGoal('Ganar masa muscular');
        $this->assertSame('Ganar masa muscular', $patient->getNutritionalGoal());

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $patient->getDietaryPlans());

        $dietaryPlan = new DietaryPlan();
        $patient->addDietaryPlan($dietaryPlan);
        $this->assertTrue($patient->getDietaryPlans()->contains($dietaryPlan));
        $patient->removeDietaryPlan($dietaryPlan);
        $this->assertFalse($patient->getDietaryPlans()->contains($dietaryPlan));
    }
}
