<?php
declare(strict_types=1);
namespace App\Tests;

use App\Infrastructure\Entity\User;

class UserControllerExtraTest extends AuthenticatedApiTestCase
{
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
}