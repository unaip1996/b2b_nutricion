<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Pgvector\Doctrine\VectorType;

#[ORM\Entity]
#[ORM\Table(name: 'document_chunks')]
class DocumentChunk
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'vector')] 
    private ?string $embedding = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\ManyToMany(targetEntity: DietaryPlan::class, mappedBy: 'documentChunks')]
    private Collection $dietaryPlans;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: ClinicalDocument::class, inversedBy: 'chunks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ClinicalDocument $clinicalDocument = null;

    public function __construct()
    {
        $this->dietaryPlans = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getEmbedding(): ?string
    {
        return $this->embedding;
    }

    public function setEmbedding(?string $embedding): static
    {
        $this->embedding = $embedding;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
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

    /**
     * @return Collection<int, DietaryPlan>
     */
    public function getDietaryPlans(): Collection
    {
        return $this->dietaryPlans;
    }

    public function addDietaryPlan(DietaryPlan $dietaryPlan): self
    {
        if (!$this->dietaryPlans->contains($dietaryPlan)) {
            $this->dietaryPlans->add($dietaryPlan);
        }

        return $this;
    }

    public function removeDietaryPlan(DietaryPlan $dietaryPlan): self
    {
        // The relationship is managed by the DietaryPlan entity (owning side).
        // This method is just a helper to keep the state consistent.
        if ($this->dietaryPlans->removeElement($dietaryPlan)) {
            // If you wanted to also remove the DietaryPlan from the chunk's side
            // $dietaryPlan->removeDocumentChunk($this);
        }

        return $this;
    }

    public function getClinicalDocument(): ?ClinicalDocument
    {
        return $this->clinicalDocument;
    }

    public function setClinicalDocument(?ClinicalDocument $clinicalDocument): self
    {
        $this->clinicalDocument = $clinicalDocument;
        return $this;
    }
}