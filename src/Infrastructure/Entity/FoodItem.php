<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'food_items')]
class FoodItem
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'kcal_per_100g')]
    private ?float $kcalPer100g = null;

    #[ORM\Column(type: 'json')]
    private array $macros = [];

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\OneToMany(mappedBy: 'foodItem', targetEntity: MealItem::class)]
    private Collection $mealItems;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->mealItems = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getKcalPer100g(): ?float
    {
        return $this->kcalPer100g;
    }

    public function setKcalPer100g(float $kcalPer100g): self
    {
        $this->kcalPer100g = $kcalPer100g;
        return $this;
    }

    public function getMacros(): array
    {
        return $this->macros;
    }

    public function setMacros(array $macros): self
    {
        $this->macros = $macros;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, MealItem>
     */
    public function getMealItems(): Collection
    {
        return $this->mealItems;
    }

    public function addMealItem(MealItem $mealItem): self
    {
        if (!$this->mealItems->contains($mealItem)) {
            $this->mealItems->add($mealItem);
            $mealItem->setFoodItem($this);
        }

        return $this;
    }

    public function removeMealItem(MealItem $mealItem): self
    {
        if ($this->mealItems->removeElement($mealItem)) {
            // set the owning side to null (unless already changed)
            if ($mealItem->getFoodItem() === $this) {
                $mealItem->setFoodItem(null);
            }
        }

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