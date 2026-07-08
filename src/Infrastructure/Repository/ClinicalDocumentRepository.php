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
     * Devuelve todos los pacientes que no han sido eliminados (Soft Delete)
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
}