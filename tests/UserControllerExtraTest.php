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

        // 1. GET Detalle
        $client->request('GET', '/api/users/' . $id);
        $this->assertResponseIsSuccessful();

        // 2. PUT Actualizar rol
        $updatePayload = ['email' => 'changed.target@test.com', 'roles' => ['ROLE_USER', 'ROLE_ADMIN']];
        $client->request('PUT', '/api/users/' . $id, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updatePayload));
        $this->assertResponseIsSuccessful();

        // 3. GET 404 (error id)
        $client->request('GET', '/api/users/00000000-0000-0000-0000-000000000000');
        $this->assertResponseStatusCodeSame(404);

        // 4. GET Listar usuarios (Cubre list())
        $client->request('GET', '/api/users');
        $this->assertResponseIsSuccessful();

        // 5. GET Perfil (Cubre showProfile())
        $client->request('GET', '/api/profile');
        $this->assertResponseIsSuccessful();

        // 6. PUT Perfil (Cubre updateProfile())
        $client->request('PUT', '/api/profile', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'admin-crud-updated@test.com', 'password' => 'newpass123']));
        $this->assertResponseIsSuccessful();

        // 7. POST (Faltan campos, cubre 400 Bad Request)
        $client->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => '']));
        $this->assertResponseStatusCodeSame(400);

        // 8. POST (Email duplicado, cubre 409 Conflict)
        $client->request('POST', '/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'changed.target@test.com', 'password' => '123']));
        $this->assertResponseStatusCodeSame(409);
    }
}