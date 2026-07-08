<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class PatientControllerTest extends AuthenticatedApiTestCase
{
    public function testListPatientsReturnsData(): void
    {
        $client = $this->createAuthenticatedClient('nutritionist@test.com', 'password', ['ROLE_USER', 'ROLE_NUTRITIONIST']);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'nutritionist@test.com']);

        // Crear un paciente asociado a este nutricionista
        $patient = new Patient();
        $patient->setName('John Doe');
        $patient->setMedicalHistoryNumber('PAC-TEST-123');

        // Gracias al cambio en AuthenticatedApiTestCase, el usuario ya tiene su perfil.
        // Lo recuperamos y lo asignamos al nuevo paciente.
        $profile = $user->getNutritionistProfile();
        $this->assertNotNull($profile, 'El perfil del nutricionista no debería ser nulo.');
        $patient->setNutritionistProfile($profile);

        $this->em->persist($patient);
        $this->em->flush();

        $client->request('GET', '/api/patients');
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['data']);
        $this->assertSame('John Doe', $response['data'][0]['name']);
    }

    public function testCreatePatientSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('creator.nutri@test.com', 'password', ['ROLE_USER', 'ROLE_NUTRITIONIST']);

        $client->request(
            'POST',
            '/api/patients',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Jane Smith',
                'email' => 'jane.smith@test.com',
                'age' => 34,
                'weight' => 65.5,
                'height' => 170
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);

        // Verificar que se ha creado en la BD
        $patient = $this->em->getRepository(Patient::class)->find($response['id']);
        $this->assertNotNull($patient);
        $this->assertSame('Jane Smith', $patient->getName());
    }

    public function testGetPatientDetailReturnsNotFoundForInvalidId(): void
    {
        $client = $this->createAuthenticatedClient();
        $invalidUuid = '123e4567-e89b-12d3-a456-426614174000';

        $client->request('GET', '/api/patients/' . $invalidUuid);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}