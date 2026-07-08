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
#[ORM\Table(name: 'dietary_plans')]
class DietaryPlan
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'dietaryPlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $kcal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\ManyToMany(targetEntity: DocumentChunk::class, inversedBy: 'dietaryPlans')]
    #[ORM\JoinTable(name: 'dietary_plan_document_chunks')]
    private Collection $documentChunks;

    #[ORM\OneToMany(mappedBy: 'dietaryPlan', targetEntity: DietDay::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dietDays;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->documentChunks = new ArrayCollection();
        $this->dietDays = new ArrayCollection();
    }

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return Collection<int, DocumentChunk>
     */
    public function getDocumentChunks(): Collection
    {
        return $this->documentChunks;
    }

    public function addDocumentChunk(DocumentChunk $documentChunk): self
    {
        if (!$this->documentChunks->contains($documentChunk)) {
            $this->documentChunks->add($documentChunk);
            $documentChunk->addDietaryPlan($this);
        }

        return $this;
    }

    public function removeDocumentChunk(DocumentChunk $documentChunk): self
    {
        if ($this->documentChunks->removeElement($documentChunk)) {
            $documentChunk->removeDietaryPlan($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, DietDay>
     */
    public function getDietDays(): Collection
    {
        return $this->dietDays;
    }

    public function addDietDay(DietDay $dietDay): self
    {
        if (!$this->dietDays->contains($dietDay)) {
            $this->dietDays->add($dietDay);
            $dietDay->setDietaryPlan($this);
        }

        return $this;
    }

    public function removeDietDay(DietDay $dietDay): self
    {
        if ($this->dietDays->removeElement($dietDay)) {
            // set the owning side to null (unless already changed)
            if ($dietDay->getDietaryPlan() === $this) {
                $dietDay->setDietaryPlan(null);
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

    public function getKcal(): ?int
    {
        return $this->kcal;
    }

    public function setKcal(?int $kcal): self
    {
        $this->kcal = $kcal;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }
}