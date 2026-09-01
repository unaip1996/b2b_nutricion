<?php

namespace App\Repository;

use App\Infrastructure\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Devuelve los usuarios paginados y filtrados.
     */
    public function searchAndPaginateActive(array $filters, int $page, int $itemsPerPage): array
    {
        // Construir condiciones WHERE de forma dinámica
        $where = ['TRUE'];
        $params = [];

        // Filtro de email
        if (!empty($filters['email'])) {
            $where[] = 'LOWER(u.email) LIKE LOWER(:email)';
            $params['email'] = '%' . strtolower($filters['email']) . '%';
        }

        // Filtro por rol (PostgreSQL JSONB contains)
        if (!empty($filters['role'])) {
            $where[] = 'u.roles::jsonb @> :role';
            $params['role'] = json_encode([$filters['role']]);
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $itemsPerPage;

        // Query para contar resultados
        $countSql = 'SELECT COUNT(*) as total FROM "users" u WHERE ' . $whereClause;
        $countStmt = $this->getEntityManager()->getConnection()->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countResult = $countStmt->executeQuery()->fetchAssociative();
        $total = (int) ($countResult['total'] ?? 0);

        // Query para obtener items usando ResultSetMapping
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(User::class, 'u');
        
        // Mapear todas las columnas de la tabla users
        $metadata = $this->getEntityManager()->getClassMetadata(User::class);
        foreach ($metadata->getColumnNames() as $column) {
            $field = $metadata->getFieldName($column);
            $rsm->addFieldResult('u', $column, $field);
        }

        $sql = 'SELECT u.* FROM "users" u WHERE ' . $whereClause . ' ORDER BY u.id DESC LIMIT :limit OFFSET :offset';
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        foreach ($params as $key => $value) {
            $query->setParameter($key, $value);
        }
        $query->setParameter('limit', $itemsPerPage, \Doctrine\DBAL\Types\Types::INTEGER);
        $query->setParameter('offset', $offset, \Doctrine\DBAL\Types\Types::INTEGER);

        $items = $query->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
