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
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $medicalHistoryNumber = null;

    #[ORM\Column(length: 50)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pathologies = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nutritionalGoal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clinicalNotes = null;

    #[ORM\Column]
    private bool $activeStatus = true;

    #[ORM\ManyToOne(inversedBy: 'patients')]
    #[ORM\JoinColumn(nullable: true)]
    private ?NutritionistProfile $nutritionistProfile = null;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Measurement::class, cascade: ['persist'])]
    private Collection $measurements;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: DietaryPlan::class)]
    private Collection $dietaryPlans;

    #[ORM\ManyToMany(targetEntity: Allergy::class, inversedBy: 'patients', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'patient_allergies')]
    private Collection $allergies;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->measurements = new ArrayCollection();
        $this->dietaryPlans = new ArrayCollection();
        $this->allergies = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPathologies(): ?string
    {
        return $this->pathologies;
    }

    public function setPathologies(?string $pathologies): self
    {
        $this->pathologies = $pathologies;
        return $this;
    }

    public function getNutritionalGoal(): ?string
    {
        return $this->nutritionalGoal;
    }

    public function setNutritionalGoal(?string $nutritionalGoal): self
    {
        $this->nutritionalGoal = $nutritionalGoal;
        return $this;
    }

    public function getClinicalNotes(): ?string
    {
        return $this->clinicalNotes;
    }

    public function setClinicalNotes(?string $clinicalNotes): self
    {
        $this->clinicalNotes = $clinicalNotes;
        return $this;
    }

    public function getMedicalHistoryNumber(): ?string
    {
        return $this->medicalHistoryNumber;
    }

    public function setMedicalHistoryNumber(string $medicalHistoryNumber): self
    {
        $this->medicalHistoryNumber = $medicalHistoryNumber;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function isActiveStatus(): bool
    {
        return $this->activeStatus;
    }

    public function setActiveStatus(bool $activeStatus): self
    {
        $this->activeStatus = $activeStatus;
        return $this;
    }

    public function getNutritionistProfile(): ?NutritionistProfile
    {
        return $this->nutritionistProfile;
    }

    public function setNutritionistProfile(?NutritionistProfile $nutritionistProfile): self
    {
        $this->nutritionistProfile = $nutritionistProfile;
        return $this;
    }

    public function getMeasurements(): Collection
    {
        return $this->measurements;
    }

    public function addMeasurement(Measurement $measurement): self
    {
        if (!$this->measurements->contains($measurement)) {
            $this->measurements->add($measurement);
            $measurement->setPatient($this);
        }
        return $this;
    }

    public function removeMeasurement(Measurement $measurement): self
    {
        if ($this->measurements->removeElement($measurement)) {
            if ($measurement->getPatient() === $this) {
                $measurement->setPatient(null);
            }
        }
        return $this;
    }

    public function getDietaryPlans(): Collection
    {
        return $this->dietaryPlans;
    }

    public function addDietaryPlan(DietaryPlan $dietaryPlan): self
    {
        if (!$this->dietaryPlans->contains($dietaryPlan)) {
            $this->dietaryPlans->add($dietaryPlan);
            $dietaryPlan->setPatient($this);
        }
        return $this;
    }

    public function removeDietaryPlan(DietaryPlan $dietaryPlan): self
    {
        if ($this->dietaryPlans->removeElement($dietaryPlan)) {
            if ($dietaryPlan->getPatient() === $this) {
                $dietaryPlan->setPatient(null);
            }
        }
        return $this;
    }

    public function getAllergies(): Collection
    {
        return $this->allergies;
    }

    public function addAllergy(Allergy $allergy): self
    {
        if (!$this->allergies->contains($allergy)) {
            $this->allergies->add($allergy);
        }
        return $this;
    }

    public function removeAllergy(Allergy $allergy): self
    {
        $this->allergies->removeElement($allergy);
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