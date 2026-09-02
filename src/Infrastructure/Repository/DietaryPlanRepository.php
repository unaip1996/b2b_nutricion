<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Infrastructure\Entity\DietaryPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
        // Construir condiciones WHERE de forma dinámica
        $where = ['d.deletedAt IS NULL'];
        $params = [];

        // Filtro 1: Nombre
        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(d.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . strtolower($filters['name']) . '%');
        }

        // Filtro 2: Calorías (Coincidencia exacta)
        if (!empty($filters['kcal'])) {
            $qb->andWhere('d.kcal = :kcal')
               ->setParameter('kcal', (int) $filters['kcal']);
        }

        // Filtro 3: Fecha (Asumiendo que 'createdAt' equivale a tu startDate en la base de datos)
        if (!empty($filters['createdAt'])) {
            try {
                // Convertimos el string que manda React (YYYY-MM-DD) en objetos de fecha de PHP
                $dateStart = new \DateTimeImmutable($filters['createdAt'] . ' 00:00:00');
                $dateEnd = new \DateTimeImmutable($filters['createdAt'] . ' 23:59:59');
                
                $qb->andWhere('d.startDate >= :dateStart AND d.startDate <= :dateEnd')
                   ->setParameter('dateStart', $dateStart)
                   ->setParameter('dateEnd', $dateEnd);
            } catch (\Exception $e) {
                // Si React envía una fecha inválida por error, evitamos que la API colapse
            }
        }

        // Filtro 4: Estado (Replicando la lógica de fechas en SQL)
        if (!empty($filters['status'])) {
            $now = new \DateTimeImmutable();
            
            switch ($filters['status']) {
                case 'Activo':
                    $qb->andWhere('d.startDate <= :now AND d.endDate >= :now')
                       ->setParameter('now', $now);
                    break;
                case 'Expirado':
                    $qb->andWhere('d.endDate < :now')
                       ->setParameter('now', $now);
                    break;
            }
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $now = new \DateTimeImmutable();
            
            if ($status === 'Activo') {
                $where[] = 'd.startDate <= :now AND d.endDate >= :now';
                $params['now'] = $now;
            } elseif ($status === 'Expirado') {
                $where[] = 'd.endDate < :now';
                $params['now'] = $now;
            } elseif ($status === 'Programado') {
                $where[] = 'd.startDate > :now';
                $params['now'] = $now;
            } elseif ($status === 'Borrador') {
                $where[] = '(d.startDate IS NULL OR d.endDate IS NULL)';
            }
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $itemsPerPage;

        // Query para contar resultados
        $countSql = 'SELECT COUNT(*) as total FROM "dietary_plans" d WHERE ' . $whereClause;
        $countStmt = $this->getEntityManager()->getConnection()->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countResult = $countStmt->executeQuery()->fetchAssociative();
        $total = (int) ($countResult['total'] ?? 0);

        // Query para obtener items usando QueryBuilder
        $qb = $this->createQueryBuilder('d');
        $qb->where($whereClause)
           ->orderBy('d.id', 'DESC')
           ->setFirstResult($offset)
           ->setMaxResults($itemsPerPage);

        foreach ($params as $key => $value) {
            $qb->setParameter($key, $value);
        }

        $items = $qb->getQuery()->getResult();

        return [
            'items' => $items,
            'total' => $total,
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

        // Aplicar filtro de búsqueda por nombre de dieta
        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(d.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . strtolower($filters['name']) . '%');
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
