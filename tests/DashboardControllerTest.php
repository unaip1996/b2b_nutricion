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

    public function testDashboardStatsCoversAllStatuses(): void
    {
        $client = $this->createAuthenticatedClient('dashboard-user@test.com');
        $patient = new Patient();
        $patient->setName('Dash Patient');
        $patient->setMedicalHistoryNumber('CG-DASH-1');
        $patient->setGender('Femenino');
        $patient->setBirthDate(new \DateTimeImmutable('1990-01-01'));
        $this->em->persist($patient);

        $dietActive = new DietaryPlan();
        $dietActive->setPatient($patient)->setName('Activa')->setStartDate(new \DateTimeImmutable('-1 day'))->setEndDate(new \DateTimeImmutable('+1 day'));
        $this->em->persist($dietActive);

        $dietExpired = new DietaryPlan();
        $dietExpired->setPatient($patient)->setName('Expirada')->setStartDate(new \DateTimeImmutable('-2 days'))->setEndDate(new \DateTimeImmutable('-1 day'));
        $this->em->persist($dietExpired);

        $dietScheduled = new DietaryPlan();
        $dietScheduled->setPatient($patient)->setName('Programada')->setStartDate(new \DateTimeImmutable('+1 day'))->setEndDate(new \DateTimeImmutable('+2 days'));
        $this->em->persist($dietScheduled);

        $this->em->flush();

        $client->request('GET', '/api/dashboard/stats');
        $this->assertResponseIsSuccessful();
    }
}