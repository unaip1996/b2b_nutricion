<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Infrastructure\Entity\NutritionistProfile;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Repository\PatientRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PatientRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(PatientRepository::class);
    }

    public function testFindAllActive(): void
    {
        $patient = new Patient(); $patient->setMedicalHistoryNumber('P-'.uniqid()); $patient->setGender('Otro'); $patient->setBirthDate(new \DateTimeImmutable());
        $patient->setName('Paciente Activo Repo');
        $this->entityManager->persist($patient);
        $this->entityManager->flush();

        $active = $this->repository->findAllActive();
        $this->assertIsArray($active);
    }

    public function testFindActiveByProfile(): void
    {
        $user = new \App\Infrastructure\Entity\User(); $user->setEmail('n-'.uniqid().'@t.com'); $user->setPassword('x'); $this->entityManager->persist($user); $profile = new NutritionistProfile(); $profile->setAccount($user);
        $this->entityManager->persist($profile);

        $patient = new Patient(); $patient->setMedicalHistoryNumber('P-'.uniqid()); $patient->setGender('Otro'); $patient->setBirthDate(new \DateTimeImmutable());
        $patient->setName('Paciente Perfil');
        $patient->setNutritionistProfile($profile);
        $this->entityManager->persist($patient);
        $this->entityManager->flush();

        $active = $this->repository->findActiveByProfile($profile);
        $this->assertIsArray($active);
    }

    public function testSearchAndPaginateActive(): void
    {
        $filters = [
            'medicalId' => 'MED',
            'name' => 'Paciente',
            'condition' => 'Diabetes',
            'objective' => 'Perder'
        ];
        
        // Pasamos null como perfil
        $result = $this->repository->searchAndPaginateActive(null, $filters, 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }
}
