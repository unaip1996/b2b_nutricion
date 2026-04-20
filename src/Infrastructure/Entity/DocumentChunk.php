<?php

namespace App\Infrastructure\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_chunks')]
class DocumentChunk
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    // ¡La magia de la IA! Un vector de 1536 dimensiones (el tamaño de OpenAI)
    #[ORM\Column(type: 'vector', length: 1536)]
    private ?string $embedding = null;

    // Getters y Setters básicos
    public function getId(): ?string { return $this->id; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getEmbedding() { return $this->embedding; }
    public function setEmbedding($embedding): self { $this->embedding = $embedding; return $this; }
}