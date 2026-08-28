<?php
declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\NutritionistProfile;
use App\Infrastructure\Entity\User;
use App\Infrastructure\Entity\Patient;
use PHPUnit\Framework\TestCase;

class NutritionistProfileTest extends TestCase
{
    public function testGettersSettersAndCollections(): void
    {
        $profile = new NutritionistProfile();
        $user = new User(); 
        $deletedAt = new \DateTimeImmutable();

        $profile->setAccount($user);
        $profile->setDeletedAt($deletedAt);

        $this->assertSame($user, $profile->getAccount());
        $this->assertSame($deletedAt, $profile->getDeletedAt());
        $this->assertNull($profile->getId());

        $patient = new Patient(); 
        
        $profile->addPatient($patient);
        $this->assertTrue($profile->getPatients()->contains($patient));
        $this->assertSame($profile, $patient->getNutritionistProfile());

        $profile->removePatient($patient);
        $this->assertFalse($profile->getPatients()->contains($patient));
        $this->assertNull($patient->getNutritionistProfile());
    }
}