<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Infrastructure\Entity\ClinicalDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClinicalDocument>
 *
 * @method ClinicalDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClinicalDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClinicalDocument[]    findAll()
 * @method ClinicalDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClinicalDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClinicalDocument::class);
    }

    /**
     * Devuelve todos los documentos que no han sido eliminados (Soft Delete)
     * * @return ClinicalDocument[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            // Opcional: Puedes ordenarlos por los creados más recientemente o por nombre
            ->orderBy('p.id', 'DESC') 
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve los documentos activos paginados y filtrados.
     * Implementa búsqueda por título de documento.
     */
    public function searchAndPaginateActive(array $filters, int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.deletedAt IS NULL')
            ->orderBy('d.ingestedAt', 'DESC');

        // Aplicar filtro de búsqueda por nombre de archivo
        if (!empty($filters['title'])) {
            $qb->andWhere('LOWER(d.fileName) LIKE LOWER(:title)')
               ->setParameter('title', '%' . strtolower($filters['title']) . '%');
        }

        // Calcular offset y limit
        $offset = ($page - 1) * $itemsPerPage;
        
        // Obtener el total de resultados
        $countQb = clone $qb;
        $total = count($countQb->getQuery()->getResult());

        // Aplicar paginación
        $qb->setFirstResult($offset)
           ->setMaxResults($itemsPerPage);

        $items = $qb->getQuery()->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}