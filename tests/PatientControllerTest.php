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
        $patient->setEmail('john.doe@test.com');
        $patient->setGender('Masculino');
        $patient->setMedicalHistoryNumber('PAC-TEST-123');
        
        // AÑADE ESTA LÍNEA (Usa una fecha válida para el test)
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01')); 

        // La propiedad 'active_status' es obligatoria en la base de datos.
        $patient->setActiveStatus(true);

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
                'gender' => 'Femenino',
                'birth_date' => '1990-05-15', // AÑADE ESTO TAMBIÉN
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

    public function testPatientCrudFlow(): void
    {
        // 1. Nos autenticamos como admin
        $client = $this->createAuthenticatedClient('admin-medico@test.com', 'password', ['ROLE_ADMIN']);

        // 2. CREATE (POST /api/patients) - Cubre la creación de paciente, biometría y alergias
        $createPayload = [
            'name' => 'Paciente de Prueba',
            'age' => 30,
            'gender' => 'Masculino',
            'weight' => 80.5,
            'height' => 180,
            'bodyFatPercentage' => 15,
            'allergies' => ['Lactosa']
        ];
        
        $client->request('POST', '/api/patients', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($createPayload));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $patientId = $response['id'];
        $this->assertNotEmpty($patientId);

        // 3. LIST (GET /api/patients) - Cubre el cálculo de edad y listado general
        $client->request('GET', '/api/patients');
        $this->assertResponseIsSuccessful();
        $listResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($listResponse['data']);

        // 4. SHOW (GET /api/patients/{id}) - Cubre la extracción del historial biométrico
        $client->request('GET', '/api/patients/' . $patientId);
        $this->assertResponseIsSuccessful();

        // 5. UPDATE (PUT /api/patients/{id}) - Cubre la lógica condicional de actualizar pesos o alergias
        $updatePayload = [
            'name' => 'Nombre Actualizado',
            'weight' => 79.0, // Al cambiar el peso, forzamos a que el controlador cree una nueva métrica
            'allergies' => ['Gluten', 'Marisco']
        ];
        $client->request('PUT', '/api/patients/' . $patientId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updatePayload));
        $this->assertResponseIsSuccessful();

        // 6. DELETE (DELETE /api/patients/{id}) - Cubre el soft delete en cascada
        $client->request('DELETE', '/api/patients/' . $patientId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testPatientEndpointsReturn404ForInvalidId(): void
    {
        $client = $this->createAuthenticatedClient('patient-user-404@test.com');
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $client->request('PUT', '/api/patients/' . $invalidUuid, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'test']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('DELETE', '/api/patients/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatientCreateHandlesMalformedJson(): void
    {
        $client = $this->createAuthenticatedClient('patient-json-err@test.com');
        $malformedJson = '{"name": "test",}';

        $client->request('POST', '/api/patients', [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}