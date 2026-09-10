<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Infrastructure\Entity\User;
use App\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(UserRepository::class);
    }

    public function testSearchAndPaginateActive(): void
    {
        $user = new User();
        $user->setEmail('user_repo@test.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('fake_password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $filters = [
            'email' => 'user_repo',
            'role' => 'ROLE_USER'
        ];

        $result = $this->repository->searchAndPaginateActive($filters, 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }
}
