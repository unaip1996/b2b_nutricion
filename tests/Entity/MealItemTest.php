<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Infrastructure\Entity\FoodItem;
use App\Infrastructure\Entity\Meal;
use App\Infrastructure\Entity\MealItem;
use PHPUnit\Framework\TestCase;

class MealItemTest extends TestCase
{
    public function testMealItemMethods(): void
    {
        $mealItem = new MealItem();
        $meal = $this->createStub(Meal::class);
        $food = $this->createStub(FoodItem::class);
        $deletedAt = new \DateTimeImmutable();

        $mealItem->setMeal($meal);
        $mealItem->setFoodItem($food);
        $mealItem->setQuantity(200.5);
        $mealItem->setUnit('gramos');
        $mealItem->setDeletedAt($deletedAt);

        $this->assertSame($meal, $mealItem->getMeal());
        $this->assertSame($food, $mealItem->getFoodItem());
        $this->assertSame(200.5, $mealItem->getQuantity());
        $this->assertSame('gramos', $mealItem->getUnit());
        $this->assertSame($deletedAt, $mealItem->getDeletedAt());
        $this->assertNull($mealItem->getId());
    }
}
