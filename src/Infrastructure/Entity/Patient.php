<?php

namespace App\Infrastructure\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'patients')]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $medicalHistoryNumber = null;

    #[ORM\Column(length: 50)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $activeStatus = true;

    // Getters y Setters básicos
    public function getId(): ?string { return $this->id; }
    public function getMedicalHistoryNumber(): ?string { return $this->medicalHistoryNumber; }
    public function setMedicalHistoryNumber(string $medicalHistoryNumber): self { $this->medicalHistoryNumber = $medicalHistoryNumber; return $this; }
    public function getGender(): ?string { return $this->gender; }
    public function setGender(string $gender): self { $this->gender = $gender; return $this; }
    public function getBirthDate(): ?\DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(\DateTimeInterface $birthDate): self { $this->birthDate = $birthDate; return $this; }
    public function isActiveStatus(): bool { return $this->activeStatus; }
    public function setActiveStatus(bool $activeStatus): self { $this->activeStatus = $activeStatus; return $this; }
}