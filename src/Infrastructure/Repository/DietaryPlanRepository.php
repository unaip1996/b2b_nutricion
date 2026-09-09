<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Infrastructure\Entity\DietaryPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<DietaryPlan>
 *
 * @method DietaryPlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method DietaryPlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method DietaryPlan[]    findAll()
 * @method DietaryPlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DietaryPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DietaryPlan::class);
    }

    /**
     * Devuelve los planes dietéticos activos paginados y filtrados.
     * Implementa búsqueda por nombre de dieta y estado.
     */
    public function searchAndPaginateActive(array $filters, int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.deletedAt IS NULL')
            ->orderBy('d.id', 'DESC');

        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(d.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . strtolower($filters['name']) . '%');
        }

        if (!empty($filters['kcal'])) {
            $qb->andWhere('d.kcal = :kcal')
               ->setParameter('kcal', (int) $filters['kcal']);
        }

        if (!empty($filters['createdAt'])) {
            try {
                $dateStart = new \DateTimeImmutable($filters['createdAt'] . ' 00:00:00');
                $dateEnd = new \DateTimeImmutable($filters['createdAt'] . ' 23:59:59');
                $qb->andWhere('d.startDate >= :dateStart AND d.startDate <= :dateEnd')
                   ->setParameter('dateStart', $dateStart)
                   ->setParameter('dateEnd', $dateEnd);
            } catch (\Exception $e) {}
        }

        if (!empty($filters['status'])) {
            $now = new \DateTimeImmutable();
            if ($filters['status'] === 'Activo') {
                $qb->andWhere('d.startDate <= :now AND d.endDate >= :now')
                   ->setParameter('now', $now);
            } elseif ($filters['status'] === 'Expirado') {
                $qb->andWhere('d.endDate < :now')
                   ->setParameter('now', $now);
            } elseif ($filters['status'] === 'Programado') {
                $qb->andWhere('d.startDate > :now')
                   ->setParameter('now', $now);
            } elseif ($filters['status'] === 'Borrador') {
                $qb->andWhere('(d.startDate IS NULL OR d.endDate IS NULL)');
            }
        }

        $qb->setFirstResult(($page - 1) * $itemsPerPage)
           ->setMaxResults($itemsPerPage);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }

    /**
     * Devuelve los planes dietéticos de un paciente específico con paginación y filtrado.
     */
    public function searchAndPaginateByPatient(string $patientId, array $filters, int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.patient = :patientId')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('patientId', $patientId)
            ->orderBy('d.id', 'DESC');

        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(d.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . strtolower($filters['name']) . '%');
        }

        $qb->setFirstResult(($page - 1) * $itemsPerPage)
           ->setMaxResults($itemsPerPage);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }
}
