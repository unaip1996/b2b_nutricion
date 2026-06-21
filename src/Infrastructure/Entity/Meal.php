<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'meals')]
class Meal
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: DietDay::class, inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DietDay $dietDay = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $mealTime = null;

    #[ORM\OneToMany(mappedBy: 'meal', targetEntity: MealItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
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

    public function getDietDay(): ?DietDay
    {
        return $this->dietDay;
    }

    public function setDietDay(?DietDay $dietDay): self
    {
        $this->dietDay = $dietDay;
        return $this;
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

    public function getMealTime(): ?\DateTimeImmutable
    {
        return $this->mealTime;
    }

    public function setMealTime(?\DateTimeImmutable $mealTime): self
    {
        $this->mealTime = $mealTime;
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
            $mealItem->setMeal($this);
        }

        return $this;
    }

    public function removeMealItem(MealItem $mealItem): self
    {
        if ($this->mealItems->removeElement($mealItem)) {
            // set the owning side to null (unless already changed)
            if ($mealItem->getMeal() === $this) {
                $mealItem->setMeal(null);
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