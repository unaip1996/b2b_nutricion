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

    public function findSimilar(array $embedding, int $limit = 5): array
    {
        $em = $this->getEntityManager();
        
        // Convertimos el vector a formato string de Postgres
        $vectorString = json_encode($embedding, JSON_THROW_ON_ERROR);

        // Usamos el mapeador nativo de Doctrine para no tener que hidratar a mano
        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(DocumentChunk::class, 'd');

        // LA QUERY CORREGIDA: document_chunks (en plural)
        $query = $em->createNativeQuery(
            'SELECT * FROM document_chunks d ORDER BY d.embedding <-> :vector LIMIT :limit',
            $rsm
        );
        
        $query->setParameter('vector', $vectorString);
        $query->setParameter('limit', $limit);

        return $query->getResult();
    }
}