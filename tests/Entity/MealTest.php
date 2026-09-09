<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Infrastructure\Entity\DietDay;
use App\Infrastructure\Entity\Meal;
use App\Infrastructure\Entity\MealItem;
use PHPUnit\Framework\TestCase;

class MealTest extends TestCase
{
    public function testMealMethods(): void
    {
        $meal = new Meal();
        $day = $this->createStub(DietDay::class);
        $mealItem = new MealItem();
        $time = new \DateTimeImmutable('14:00');
        $deletedAt = new \DateTimeImmutable();

        $meal->setDietDay($day);
        $meal->setName('Comida');
        $meal->setMealTime($time);
        $meal->setDeletedAt($deletedAt);

        $this->assertSame($day, $meal->getDietDay());
        $this->assertSame('Comida', $meal->getName());
        $this->assertSame($time, $meal->getMealTime());
        $this->assertSame($deletedAt, $meal->getDeletedAt());
        $this->assertNull($meal->getId());

        $meal->addMealItem($mealItem);
        $this->assertTrue($meal->getMealItems()->contains($mealItem));
        $this->assertSame($meal, $mealItem->getMeal());

        $meal->removeMealItem($mealItem);
        $this->assertFalse($meal->getMealItems()->contains($mealItem));
        $this->assertNull($mealItem->getMeal());
    }
}
