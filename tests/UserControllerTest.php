<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AuthenticatedApiTestCase
{
    public function testGetOwnProfileSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('user-profile@test.com');

        $client->request('GET', '/api/profile');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('user-profile@test.com', $response['data']['email']);
        $this->assertContains('ROLE_USER', $response['data']['roles']);
    }

    public function testUpdateOwnProfile(): void
    {
        $client = $this->createAuthenticatedClient('user-to-update@test.com');

        $client->request(
            'PUT',
            '/api/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'updated-email@test.com'])
        );

        $this->assertResponseIsSuccessful();

        // Verificamos que el cambio se ha guardado
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'updated-email@test.com']);
        $this->assertNotNull($user);
    }

    public function testAdminCanListAllUsers(): void
    {
        // Creamos un cliente autenticado como administrador.
        // El helper createAuthenticatedClient ya nos da acceso a su EntityManager ($this->em).
        $adminClient = $this->createAuthenticatedClient('admin-lister@test.com', 'password', ['ROLE_ADMIN']);

        // Para asegurar que la lista no está vacía, creamos un segundo usuario directamente
        // en la base de datos usando el EntityManager del cliente actual.
        $passwordHasher = $adminClient->getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $regularUser = new User();
        $regularUser->setEmail('another.user@test.com');
        $regularUser->setRoles(['ROLE_USER']);
        $regularUser->setPassword($passwordHasher->hashPassword($regularUser, 'password'));
        $this->em->persist($regularUser);
        $this->em->flush();

        // Realizamos la petición para listar usuarios.
        $adminClient->request('GET', '/api/users');
        $this->assertResponseIsSuccessful();

        $response = json_decode($adminClient->getResponse()->getContent(), true);
        $this->assertIsArray($response['data']);
        $this->assertGreaterThanOrEqual(1, count($response['data']), 'El administrador debería poder ver al menos un usuario en la lista.');
    }

    public function testRegularUserCannotListUsers(): void
    {
        $client = $this->createAuthenticatedClient('regular.user@test.com', 'password', ['ROLE_USER']);
        $client->request('GET', '/api/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanCreateUser(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-creator@test.com', 'password', ['ROLE_ADMIN']);

        $adminClient->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'newly.created@test.com',
                'password' => 'a-secure-password',
                'roles' => ['ROLE_USER']
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}