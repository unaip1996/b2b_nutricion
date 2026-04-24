<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'measurements')]
class Measurement
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\Column]
    private ?float $weight = null;

    #[ORM\Column(name: 'body_fat_percentage')]
    private ?float $bodyFatPercentage = null;

    #[ORM\Column(name: 'muscle_mass')]
    private ?float $muscleMass = null;

    #[ORM\Column(name: 'waist_circumference')]
    private ?float $waistCircumference = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $takenAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getBodyFatPercentage(): ?float
    {
        return $this->bodyFatPercentage;
    }

    public function setBodyFatPercentage(float $bodyFatPercentage): self
    {
        $this->bodyFatPercentage = $bodyFatPercentage;
        return $this;
    }

    public function getMuscleMass(): ?float
    {
        return $this->muscleMass;
    }

    public function setMuscleMass(float $muscleMass): self
    {
        $this->muscleMass = $muscleMass;
        return $this;
    }

    public function getWaistCircumference(): ?float
    {
        return $this->waistCircumference;
    }

    public function setWaistCircumference(float $waistCircumference): self
    {
        $this->waistCircumference = $waistCircumference;
        return $this;
    }

    public function getTakenAt(): ?\DateTimeImmutable
    {
        return $this->takenAt;
    }

    public function setTakenAt(\DateTimeImmutable $takenAt): self
    {
        $this->takenAt = $takenAt;
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