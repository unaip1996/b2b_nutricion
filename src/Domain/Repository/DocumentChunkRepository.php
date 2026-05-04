<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\DocumentChunkRepositoryInterface;
use App\Infrastructure\Entity\DocumentChunk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentChunk>
 */
class DocumentChunkRepository extends ServiceEntityRepository implements DocumentChunkRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        // Le indicamos a Doctrine qué entidad va a gestionar este repositorio
        parent::__construct($registry, DocumentChunk::class);
    }

    public function save(DocumentChunk $chunk): void
    {
        $em = $this->getEntityManager();
        $em->persist($chunk);
        $em->flush();
    }
}