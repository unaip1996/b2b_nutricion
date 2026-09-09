<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Repository\DietaryPlanRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DietaryPlanRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(DietaryPlanRepository::class);
    }

    public function testSearchAndPaginateActive(): void
    {
        $patient = new \App\Infrastructure\Entity\Patient();
        $patient->setName('Paciente Repo');
        $patient->setMedicalHistoryNumber('12345-' . uniqid());
        $patient->setGender('Masculino');
        $patient->setBirthDate(new \DateTimeImmutable());
        $this->entityManager->persist($patient);

        $plan = new DietaryPlan();
        $plan->setPatient($patient);
        $plan->setName('Dieta 1500 kcal Repo');
        $plan->setKcal(1500);
        $plan->setStartDate(new \DateTimeImmutable('2026-01-01'));
        $plan->setEndDate(new \DateTimeImmutable('2026-12-31'));
        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $filters = [
            'name' => '1500',
            'kcal' => 1500,
            'createdAt' => '2026-01-01',
            'status' => 'Activo'
        ];

        $result = $this->repository->searchAndPaginateActive($filters, 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function testSearchAndPaginateByPatient(): void
    {
        $patient = new \App\Infrastructure\Entity\Patient();
        $patient->setName('Paciente Repo 2');
        $patient->setMedicalHistoryNumber('123456-' . uniqid());
        $patient->setGender('Masculino');
        $patient->setBirthDate(new \DateTimeImmutable());
        $this->entityManager->persist($patient);
        
        $plan = new DietaryPlan();
        $plan->setPatient($patient);
        $plan->setName('Dieta Paciente 1');
        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $result = $this->repository->searchAndPaginateByPatient((string) $patient->getId(), ['name' => 'Paciente'], 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
    }
}
