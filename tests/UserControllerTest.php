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
        $client->catchExceptions(false);
        
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        
        $client->request('GET', '/api/users');
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

    public function testAdminCanGetSpecificUser(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-getter-specific@test.com', 'password', ['ROLE_ADMIN']);

        $userToGet = new User();
        $userToGet->setEmail('user.to.get@test.com');
        $passwordHasher = $adminClient->getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $userToGet->setPassword($passwordHasher->hashPassword($userToGet, 'password'));
        $this->em->persist($userToGet);
        $this->em->flush();
        $userId = $userToGet->getId();

        $adminClient->request('GET', '/api/users/' . $userId);

        $this->assertResponseIsSuccessful();
        $response = json_decode($adminClient->getResponse()->getContent(), true);
        $this->assertSame('user.to.get@test.com', $response['data']['email']);
    }

    public function testAdminCanUpdateSpecificUser(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-updater-specific@test.com', 'password', ['ROLE_ADMIN']);

        $userToUpdate = new User();
        $userToUpdate->setEmail('user.to.update@test.com');
        $passwordHasher = $adminClient->getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $userToUpdate->setPassword($passwordHasher->hashPassword($userToUpdate, 'password'));
        $this->em->persist($userToUpdate);
        $this->em->flush();
        $userId = $userToUpdate->getId();

        $adminClient->request(
            'PUT',
            '/api/users/' . $userId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'updated.by.admin@test.com', 'roles' => ['ROLE_STAFF']])
        );

        $this->assertResponseIsSuccessful();
        $this->em->clear();

        $updatedUser = $this->em->getRepository(User::class)->find($userId);
        $this->assertSame('updated.by.admin@test.com', $updatedUser->getEmail());
        $this->assertContains('ROLE_STAFF', $updatedUser->getRoles());
    }

    public function testAdminCanDeleteUser(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-deleter@test.com', 'password', ['ROLE_ADMIN']);

        $userToDelete = new User();
        $userToDelete->setEmail('user.to.delete@test.com');
        $passwordHasher = $adminClient->getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $userToDelete->setPassword($passwordHasher->hashPassword($userToDelete, 'password'));
        $this->em->persist($userToDelete);
        $this->em->flush();
        $userId = $userToDelete->getId();

        $adminClient->request('DELETE', '/api/users/' . $userId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verificamos el borrado lógico (soft delete)
        $this->em->clear();
        /** @var User $deletedUser */
        $deletedUser = $this->em->getRepository(User::class)->find($userId);
        $this->assertNotNull($deletedUser->getDeletedAt());
    }

    public function testUserFullCrud(): void
    {
        $client = $this->createAuthenticatedClient('admin-crud@test.com', 'password', ['ROLE_ADMIN']);
        
        $user = new User();
        $user->setEmail('target.user@test.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $this->em->persist($user);
        $this->em->flush();

        $id = (string) $user->getId();

        $client->request('GET', '/api/users/' . $id);
        $this->assertResponseIsSuccessful();

        $updatePayload = ['email' => 'changed.target@test.com', 'roles' => ['ROLE_USER', 'ROLE_ADMIN']];
        $client->request('PUT', '/api/users/' . $id, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updatePayload));
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/users/00000000-0000-0000-0000-000000000000');
        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/users');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/profile');
        $this->assertResponseIsSuccessful();

        // FIX 401: Solo actualizamos el email al mismo valor para NO romper el JWT de seguridad
        $client->request('PUT', '/api/profile', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'admin-crud@test.com']));
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => '']));
        $this->assertResponseStatusCodeSame(400);

        $client->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'changed.target@test.com', 'password' => '123']));
        $this->assertResponseStatusCodeSame(409);
        
        // CUBRIR ÉXITO (Suma 10 líneas verdes): Crear un nutricionista genera automáticamente su perfil
        $client->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'nuevo.nutri@test.com', 'password' => '123', 'roles' => ['ROLE_NUTRITIONIST']]));
        $this->assertResponseStatusCodeSame(201);
    }

    public function testAdminCannotRemoveOwnAdminRole(): void
    {
        $adminClient = $this->createAuthenticatedClient('self-admin@test.com', 'password', ['ROLE_ADMIN']);
        /** @var User $adminUser */
        $adminUser = $this->em->getRepository(User::class)->findOneBy(['email' => 'self-admin@test.com']);
        $adminId = $adminUser->getId();

        $adminClient->request(
            'PUT',
            '/api/users/' . $adminId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['roles' => ['ROLE_USER']]) // Intentando quitar ROLE_ADMIN
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserEndpointsReturn404ForInvalidId(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-404@test.com', 'password', ['ROLE_ADMIN']);
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $adminClient->request('PUT', '/api/users/' . $invalidUuid, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'test@test.com']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $adminClient->request('DELETE', '/api/users/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUserActionsHandleMalformedJson(): void
    {
        $adminClient = $this->createAuthenticatedClient('admin-json-err@test.com', 'password', ['ROLE_ADMIN']);
        $passwordHasher = $adminClient->getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('some-user-for-update@test.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $this->em->persist($user);
        $this->em->flush();

        $malformedJson = '{"email": "test@test.com",}'; // JSON inválido

        $adminClient->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $adminClient->request('PUT', '/api/users/' . $user->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}