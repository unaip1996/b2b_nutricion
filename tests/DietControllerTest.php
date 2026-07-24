<?php
declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\DietaryPlan;

class DietControllerTest extends AuthenticatedApiTestCase
{
    public function testDietCrudFlow(): void
    {
        $client = $this->createAuthenticatedClient('admin-diet@test.com', 'password', ['ROLE_ADMIN']);
        
        // 1. Preparar datos en base de datos para simular
        $patient = new Patient();
        $patient->setName('Paciente Dieta Test');
        $patient->setMedicalHistoryNumber('PAC-888');
        $patient->setGender('Masculino');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        $this->em->persist($patient);
        
        $diet = new DietaryPlan();
        $diet->setName('Dieta Base');
        $diet->setKcal(2000);
        $diet->setPatient($patient);
        $diet->setStartDate(new \DateTimeImmutable('-1 day'));
        $diet->setEndDate(new \DateTimeImmutable('+5 days'));
        $this->em->persist($diet);
        $this->em->flush();

        $patientId = (string) $patient->getId();
        $dietId = (string) $diet->getId();

        // 2. Listar dietas del paciente
        $client->request('GET', '/api/patients/' . $patientId . '/diets');
        $this->assertResponseIsSuccessful();

        // 3. Obtener detalle de dieta
        $client->request('GET', '/api/diets/' . $dietId);
        $this->assertResponseIsSuccessful();

        // 4. Actualizar dieta masivamente (Cubre más de 60 líneas de bucles)
        $updatePayload = [
            'name' => 'Dieta Modificada',
            'kcal' => 2200,
            'observations' => 'Sin sal',
            'startDate' => '2026-08-01',
            'endDate' => '2026-08-31',
            'days' => [
                [
                    'dayNumber' => 1,
                    'meals' => [
                        [
                            'name' => 'Desayuno',
                            'mealTime' => '08:00',
                            'items' => [
                                ['foodName' => 'Avena', 'quantity' => 50, 'unit' => 'g']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $client->request('PUT', '/api/diets/' . $dietId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updatePayload));
        $this->assertResponseIsSuccessful();

        // 5. Soft Delete
        $client->request('DELETE', '/api/diets/' . $dietId);
        $this->assertResponseIsSuccessful();
    }

    public function testGenerateDietValidationFailsWhenMissingParams(): void
    {
        $client = $this->createAuthenticatedClient('admin-gen@test.com', 'password', ['ROLE_USER']);
        
        // Intentar generar dieta sin parámetros (Fuerza las validaciones de error)
        $client->request('POST', '/api/diets/generate', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $this->assertResponseStatusCodeSame(400);
    }
}