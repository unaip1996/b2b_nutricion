<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\DocumentChunkRepositoryInterface;
use App\Infrastructure\Entity\DocumentChunk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\ParameterType;

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

    /**
     * Realiza una búsqueda de similitud vectorial (Semantic Search).
     * Devuelve el texto de los fragmentos más relevantes para la consulta.
     */
    public function findSimilarText(array $queryVector, int $limit = 3): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $vectorString = json_encode($queryVector);

        $sql = "
            SELECT content 
            FROM document_chunks 
            WHERE deleted_at IS NULL
            ORDER BY embedding <-> :vector 
            LIMIT :limit
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('vector', $vectorString);
        
        // CORRECCIÓN AQUÍ: Usamos ParameterType::INTEGER
        $stmt->bindValue('limit', $limit, ParameterType::INTEGER);

        $results = $stmt->executeQuery()->fetchAllAssociative();

        return array_column($results, 'content');
    }

    /**
     * Recupera los documentos médicos globales indexados en la base de conocimiento,
     * agrupándolos por archivo.
     */
    public function findGroupedDocuments(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                dc.metadata->>'file_name' as file_name,
                COUNT(dc.id) as chunk_count,
                MAX(dc.metadata->>'ingested_at') as ingested_at
            FROM document_chunks dc
            WHERE dc.deleted_at IS NULL
            GROUP BY dc.metadata->>'file_name'
            ORDER BY ingested_at DESC
        ";

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Realiza un borrado lógico (Soft Delete) de todos los fragmentos 
     * vectoriales asociados a un documento específico.
     */
    public function deleteByFileName(string $fileName): void
    {
        $conn = $this->getEntityManager()->getConnection();

        // En lugar de DELETE, hacemos un UPDATE de la columna deleted_at
        // Solo actualizamos los que no estén ya borrados
        $sql = "
            UPDATE document_chunks 
            SET deleted_at = NOW() 
            WHERE metadata->>'file_name' = :fileName 
            AND deleted_at IS NULL
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('fileName', $fileName);
        $stmt->executeQuery();
    }

    /**
     * Devuelve las ENTIDADES completas basadas en similitud vectorial.
     */
    public function findSimilarChunkEntities(string $vector, int $limit = 4): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // 1. Inyectamos el límite directamente como entero para evitar fallos de PDO con PostgreSQL
        $sql = "SELECT id FROM document_chunks WHERE deleted_at IS NULL ORDER BY embedding <-> :vector LIMIT " . (int)$limit;
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('vector', $vector); // Solo queda 1 parámetro por bindear
        
        $result = $stmt->executeQuery()->fetchAllAssociative();
        
        $ids = array_column($result, 'id');
        
        if (empty($ids)) {
            return [];
        }

        // 2. Devolvemos los OBJETOS reales de Doctrine usando sus IDs
        return $this->findBy(['id' => $ids]);
    }
}