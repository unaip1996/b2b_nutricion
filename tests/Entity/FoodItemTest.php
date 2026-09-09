<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Infrastructure\Entity\FoodItem;
use App\Infrastructure\Entity\MealItem;
use PHPUnit\Framework\TestCase;

class FoodItemTest extends TestCase
{
    public function testFoodItemMethods(): void
    {
        $food = new FoodItem();
        $mealItem = new MealItem();
        $deletedAt = new \DateTimeImmutable();

        $food->setName('Arroz');
        $food->setKcalPer100g(130.0);
        $food->setMacros(['proteins' => 2.7, 'carbs' => 28, 'fats' => 0.3]);
        $food->setCategory('Cereales');
        $food->setDeletedAt($deletedAt);

        $this->assertSame('Arroz', $food->getName());
        $this->assertSame(130.0, $food->getKcalPer100g());
        $this->assertSame(['proteins' => 2.7, 'carbs' => 28, 'fats' => 0.3], $food->getMacros());
        $this->assertSame('Cereales', $food->getCategory());
        $this->assertSame($deletedAt, $food->getDeletedAt());
        $this->assertNull($food->getId());

        $food->addMealItem($mealItem);
        $this->assertTrue($food->getMealItems()->contains($mealItem));
        $this->assertSame($food, $mealItem->getFoodItem());

        $food->removeMealItem($mealItem);
        $this->assertFalse($food->getMealItems()->contains($mealItem));
        $this->assertNull($mealItem->getFoodItem());
    }
}
