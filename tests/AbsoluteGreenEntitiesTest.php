<?php
declare(strict_types=1);
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Entity\User;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\NutritionistProfile;
use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Entity\DocumentChunk;

class AbsoluteGreenEntitiesTest extends TestCase
{
    public function testUserRemaining(): void
    {
        $user = new User();
        $date = new \DateTimeImmutable();
        
        $user->setLastLogin($date);
        $this->assertSame($date, $user->getLastLogin());
        
        $user->setDeletedAt($date);
        $this->assertSame($date, $user->getDeletedAt());
        
        $profile = new NutritionistProfile();
        $user->setNutritionistProfile($profile);
        $this->assertSame($profile, $user->getNutritionistProfile());
        
        // El método inverso automático de tu entidad
        $this->assertSame($user, $profile->getAccount()); 
        
        // Roles base
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_USER', $user->getRoles());
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
    
    public function testClinicalDocumentRemaining(): void
    {
        $doc = new ClinicalDocument();
        $this->assertNull($doc->getId());
        
        $date = new \DateTimeImmutable();
        $doc->setDeletedAt($date);
        $this->assertSame($date, $doc->getDeletedAt());
        
        $chunk = new DocumentChunk();
        $doc->addChunk($chunk);
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $doc->getChunks());
        $this->assertTrue($doc->getChunks()->contains($chunk));
    }
    
    public function testDocumentChunkRemaining(): void
    {
        $chunk = new DocumentChunk();
        $date = new \DateTimeImmutable();
        $chunk->setDeletedAt($date);
        $this->assertSame($date, $chunk->getDeletedAt());
    }
}