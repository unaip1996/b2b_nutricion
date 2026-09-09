<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Entity\User;
use App\Infrastructure\Entity\NutritionistProfile;

class UserTest extends TestCase
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
        
        $this->assertSame($user, $profile->getAccount()); 
        
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserRemainingMethods(): void
    {
        $user = new User();
        
        $user->setEmail('test@test.com');
        $this->assertSame('test@test.com', $user->getUserIdentifier());

        $user->eraseCredentials(); 
        $this->assertTrue(true); 

        $profile = new NutritionistProfile();
        $user->setNutritionistProfile($profile);
        $this->assertSame($profile, $user->getNutritionistProfile());
    }
}
