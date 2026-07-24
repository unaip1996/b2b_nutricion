<?php
declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\Patient;

class DashboardControllerTest extends AuthenticatedApiTestCase
{
    public function testGetStatsFlow(): void
    {
        $client = $this->createAuthenticatedClient('admin-dashboard@test.com', 'password', ['ROLE_USER']);

        $patient = new Patient();
        $patient->setName('Paciente Dash');
        $patient->setMedicalHistoryNumber('PAC-DASH-999');
        $patient->setGender('Hombre');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        $this->em->persist($patient);

        $diet = new DietaryPlan();
        $diet->setName('Dieta Activa Dash');
        $diet->setPatient($patient);
        $diet->setStartDate(new \DateTimeImmutable('-1 day'));
        $diet->setEndDate(new \DateTimeImmutable('+5 days'));
        $this->em->persist($diet);

        $this->em->flush();
        
        $client->request('GET', '/api/dashboard/stats');
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('kpis', $response['data']);
        $this->assertArrayHasKey('recentDiets', $response['data']);
        $this->assertArrayHasKey('chartData', $response['data']);
    }
}