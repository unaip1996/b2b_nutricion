<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\DietaryPlan;
use Symfony\Component\HttpFoundation\Response;
use App\Infrastructure\Controller\DietController;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class DietControllerTest extends AuthenticatedApiTestCase
{
    public function testDietCrudFlow(): void
    {
        $client = $this->createAuthenticatedClient('admin-diet@test.com', 'password', ['ROLE_ADMIN']);

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

        $client->request('GET', '/api/patients/' . $patientId . '/diets');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/diets/' . $dietId);
        $this->assertResponseIsSuccessful();

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

        $client->request('DELETE', '/api/diets/' . $dietId);
        $this->assertResponseIsSuccessful();
    }

    public function testGenerateDietValidationFailsWhenMissingParams(): void
    {
        $client = $this->createAuthenticatedClient('admin-gen@test.com', 'password', ['ROLE_USER']);

        $client->request('POST', '/api/diets/generate', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testDietEndpointsReturn404ForInvalidId(): void
    {
        $client = $this->createAuthenticatedClient('diet-user-404@test.com');
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $client->request('GET', '/api/diets/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('PUT', '/api/diets/' . $invalidUuid, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'test']));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('DELETE', '/api/diets/' . $invalidUuid);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGenerateDietSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('admin@test.com', 'password', ['ROLE_ADMIN']);

        $patient = new \App\Infrastructure\Entity\Patient();
        $patient->setName('Paciente de Prueba');
        $patient->setMedicalHistoryNumber('MHN-' . uniqid()); 
        $patient->setGender('Otro');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        
        $this->em->persist($patient);
        $this->em->flush();

        $patientId = (string) $patient->getId();

        $client->request(
            'POST',
            '/api/diets/generate', 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['patientId' => $patientId, 'query' => 'Quiero ganar mÃºsculo']) 
        );

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $responseData = json_decode($content, true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('dietary_proposal', $responseData['data']);

        $proposalString = $responseData['data']['dietary_proposal'];

        $this->assertStringContainsString('totalKcal', $proposalString);
        $this->assertStringContainsString('2000', $proposalString);
        $this->assertStringContainsString('Dieta generada por entorno de test', $proposalString);
    }

    public function testDietUpdateHandlesMalformedJson(): void
    {
        $client = $this->createAuthenticatedClient('diet-json-err@test.com');

        $patient = new Patient();
        $patient->setName('Patient for Diet JSON test');
        $patient->setMedicalHistoryNumber('PAC-JSON-DIET');
        $patient->setGender('Masculino');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        $this->em->persist($patient);

        $diet = new DietaryPlan();
        $diet->setName('Diet for JSON test');
        $diet->setPatient($patient);
        $diet->setStartDate(new \DateTimeImmutable());
        $diet->setEndDate(new \DateTimeImmutable('+1 day'));
        $this->em->persist($diet);
        $this->em->flush();

        $dietId = (string) $diet->getId();
        $malformedJson = '{"name": "test",}';

        $client->request('PUT', '/api/diets/' . $dietId, [], [], ['CONTENT_TYPE' => 'application/json'], $malformedJson);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testDietControllerExceptionsAndSuccess(): void
    {
        $useCase = $this->createStub(GenerateClinicalDietUseCase::class);
        $logger = $this->createStub(LoggerInterface::class);
        $controller = new DietController();
        
        $reqGenerate = new Request([], [], [], [], [], [], json_encode(['patientId'=>'1','query'=>'t']));
        $this->assertEquals(200, $controller->generateDiet($reqGenerate, $useCase)->getStatusCode());

        $useCaseThrow = $this->createStub(GenerateClinicalDietUseCase::class);
        $useCaseThrow->method('execute')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->generateDiet($reqGenerate, $useCaseThrow)->getStatusCode());
        
        $em = $this->createStub(EntityManagerInterface::class);
        $dietRepo = $this->createStub(\App\Infrastructure\Repository\DietaryPlanRepository::class);
        $dietRepo->method('searchAndPaginateByPatient')->willThrowException(new \Exception('Test'));
        $req = new Request();
        $this->assertEquals(500, $controller->listPatientDiets('1', $req, $dietRepo)->getStatusCode());
        
        $em->method('getRepository')->willThrowException(new \Exception('Test'));
        $this->assertEquals(500, $controller->getDietDetail('1', $em, $logger)->getStatusCode());
        $this->assertEquals(500, $controller->deleteDiet('1', $em)->getStatusCode());
        
        $reqPut = new Request([], [], [], [], [], [], '{}');
        $this->assertEquals(500, $controller->updateDiet('1', $reqPut, $em, $logger)->getStatusCode());
    }
}

