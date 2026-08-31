<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Infrastructure\Entity\NutritionistProfile;
use App\Infrastructure\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Patient>
 *
 * @method Patient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Patient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Patient[]    findAll()
 * @method Patient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patient::class);
    }

    /**
     * Devuelve todos los pacientes que no han sido eliminados (Soft Delete)
     * * @return Patient[]
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
     * Devuelve los pacientes activos que pertenecen a un nutricionista concreto.
     */
    public function findActiveByProfile($profile): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            // Ojo: Asegúrate de que 'nutritionistProfile' coincide con el nombre de la propiedad en tu entidad Patient
            ->andWhere('p.nutritionistProfile = :profile') 
            ->setParameter('profile', $profile)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve los pacientes activos paginados y filtrados.
     * Si $profile es null (Admin), devuelve los de toda la clínica.
     */
    public function searchAndPaginateActive(?NutritionistProfile $profile, array $filters, int $page, int $itemsPerPage): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL') // Tu condición real de paciente activo
            ->orderBy('p.id', 'DESC');        // Los más recientes primero, como tenías originalemente

        // 1. Filtrar por el profesional asignado (si no es admin)
        if ($profile !== null) {
            $qb->andWhere('p.nutritionistProfile = :profile') 
               ->setParameter('profile', $profile);
        }

        // 2. Aplicar filtros dinámicos del frontend (búsqueda parcial ignorando mayúsculas)
        if (!empty($filters['medicalId'])) {
            $qb->andWhere('LOWER(p.medicalHistoryNumber) LIKE LOWER(:medicalId)')
               ->setParameter('medicalId', '%' . strtolower($filters['medicalId']) . '%');
        }
        
        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . strtolower($filters['name']) . '%');
        }
        
        if (!empty($filters['condition'])) {
            $qb->andWhere('LOWER(p.pathologies) LIKE LOWER(:condition)')
               ->setParameter('condition', '%' . strtolower($filters['condition']) . '%');
        }
        
        if (!empty($filters['objective'])) {
            $qb->andWhere('LOWER(p.nutritionalGoal) LIKE LOWER(:objective)')
               ->setParameter('objective', '%' . strtolower($filters['objective']) . '%');
        }

        // 3. Configurar la paginación física en SQL (LIMIT y OFFSET)
        $qb->setFirstResult(($page - 1) * $itemsPerPage)
           ->setMaxResults($itemsPerPage);

        // Envolvemos la query en el Paginator de Doctrine para que cuente el total real
        // antes de aplicar el LIMIT (necesario para que Next.js sepa cuántas páginas hay)
        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator)
        ];
    }
}