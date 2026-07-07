<?php

declare(strict_types=1);

namespace App\Infrastructure\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'clinical_documents')]
class ClinicalDocument
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $ingestedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    // MAGIA: Borrado en cascada. Si borramos el documento, mueren sus chunks.
    #[ORM\OneToMany(mappedBy: 'clinicalDocument', targetEntity: DocumentChunk::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $chunks;

    public function __construct()
    {
        $this->chunks = new ArrayCollection();
        $this->ingestedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getIngestedAt(): ?\DateTimeImmutable
    {
        return $this->ingestedAt;
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

    public function getChunks(): Collection
    {
        return $this->chunks;
    }

    public function addChunk(DocumentChunk $chunk): self
    {
        if (!$this->chunks->contains($chunk)) {
            $this->chunks->add($chunk);
            $chunk->setClinicalDocument($this);
        }
        return $this;
    }
}