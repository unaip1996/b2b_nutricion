<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Infrastructure\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}