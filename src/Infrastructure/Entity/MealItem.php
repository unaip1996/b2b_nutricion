<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'meal_items')]
class MealItem
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Meal::class, inversedBy: 'mealItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Meal $meal = null;

    #[ORM\ManyToOne(targetEntity: FoodItem::class, inversedBy: 'mealItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FoodItem $foodItem = null;

    #[ORM\Column]
    private ?float $quantity = null;

    #[ORM\Column(length: 50)]
    private ?string $unit = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getMeal(): ?Meal
    {
        return $this->meal;
    }

    public function setMeal(?Meal $meal): self
    {
        $this->meal = $meal;
        return $this;
    }

    public function getFoodItem(): ?FoodItem
    {
        return $this->foodItem;
    }

    public function setFoodItem(?FoodItem $foodItem): self
    {
        $this->foodItem = $foodItem;
        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}