<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Infrastructure\Entity\DietDay;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\Meal;
use PHPUnit\Framework\TestCase;

class DietDayTest extends TestCase
{
    public function testDietDayMethods(): void
    {
        $day = new DietDay();
        $plan = $this->createStub(DietaryPlan::class);
        $meal = new Meal();
        $deletedAt = new \DateTimeImmutable();

        $day->setDietaryPlan($plan);
        $day->setDayNumber(3);
        $day->setDeletedAt($deletedAt);

        $this->assertSame($plan, $day->getDietaryPlan());
        $this->assertSame(3, $day->getDayNumber());
        $this->assertSame($deletedAt, $day->getDeletedAt());
        $this->assertNull($day->getId());

        $day->addMeal($meal);
        $this->assertTrue($day->getMeals()->contains($meal));
        $this->assertSame($day, $meal->getDietDay());

        $day->removeMeal($meal);
        $this->assertFalse($day->getMeals()->contains($meal));
        $this->assertNull($meal->getDietDay());
    }
}
