<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\DietDay;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\FoodItem;
use App\Infrastructure\Entity\Meal;
use App\Infrastructure\Entity\MealItem;
use PHPUnit\Framework\TestCase;

class DietaryEntitiesTest extends TestCase
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