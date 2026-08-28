<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Infrastructure\Entity\Patient;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\ClinicalDocument;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Dashboard', description: 'Endpoints analíticos para el cuadro de mando de la clínica')]
readonly class DashboardController
{
    #[Route('/api/dashboard/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getStats(EntityManagerInterface $em): JsonResponse
    {
        try {
            $now = new \DateTimeImmutable();

            // TODO: En una fase posterior multi-tenant, filtrar aquí usando el token: 
            // $nutritionist = $this->getUser()->getNutritionistProfile();

            // 1. Cómputo de KPIs Principales
            $totalPatients = $em->getRepository(Patient::class)->count(['deletedAt' => null]);
            $totalDietsGenerated = $em->getRepository(DietaryPlan::class)->count(['deletedAt' => null]);
            $totalDocumentsRAG = $em->getRepository(ClinicalDocument::class)->count(['deletedAt' => null]);

            // 2. Últimas dietas generadas (Historial Reciente)
            $recentDietPlans = $em->getRepository(DietaryPlan::class)->findBy(
                ['deletedAt' => null],
                ['id' => 'DESC'],
                5
            );

            // Inicializamos contadores para el gráfico útil de estados
            $statusCounts = [
                'Activos' => 0,
                'Programados' => 0,
                'Expirados' => 0
            ];

            $recentDietsData = [];
            
            // Recorremos todos los planes (o podrías hacer una query agregada) para clasificar estados
            $allDietPlans = $em->getRepository(DietaryPlan::class)->findBy(['deletedAt' => null]);
            
            foreach ($allDietPlans as $plan) {
                $status = 'Borrador';
                if ($plan->getStartDate() && $plan->getEndDate()) {
                    if ($now >= $plan->getStartDate() && $now <= $plan->getEndDate()) {
                        $status = 'Activo';
                        $statusCounts['Activos']++;
                    } elseif ($now > $plan->getEndDate()) {
                        $status = 'Expirado';
                        $statusCounts['Expirados']++;
                    } else {
                        $status = 'Programado';
                        $statusCounts['Programados']++;
                    }
                }
            }

            // Mapeamos solo los 5 recientes para la tabla
            foreach ($recentDietPlans as $plan) {
                $status = 'Borrador';
                if ($plan->getStartDate() && $plan->getEndDate()) {
                    if ($now >= $plan->getStartDate() && $now <= $plan->getEndDate()) {
                        $status = 'Activo';
                    } elseif ($now > $plan->getEndDate()) {
                        $status = 'Expirado';
                    } else {
                        $status = 'Programado';
                    }
                }
                $recentDietsData[] = [
                    'id' => $plan->getId()->toRfc4122(),
                    'patientName' => $plan->getPatient() ? $plan->getPatient()->getName() : 'Anónimo',
                    'name' => $plan->getName() ?? 'Plan Nutricional',
                    'kcal' => $plan->getKcal() ?? 2000,
                    'status' => $status,
                    'date' => $plan->getStartDate() ? $plan->getStartDate()->format('Y-m-d') : $now->format('Y-m-d')
                ];
            }

            // Formateamos la distribución para el gráfico circular
            $chartData = [
                ['name' => 'Activos', 'value' => $statusCounts['Activos']],
                ['name' => 'Programados', 'value' => $statusCounts['Programados']],
                ['name' => 'Expirados', 'value' => $statusCounts['Expirados']],
            ];

            return new JsonResponse([
                'data' => [
                    'kpis' => [
                        'totalPatients' => $totalPatients,
                        'totalDiets' => $totalDietsGenerated,
                        'knowledgeBaseSize' => $totalDocumentsRAG
                    ],
                    'recentDiets' => $recentDietsData,
                    'chartData' => $chartData
                ]
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error al procesar analíticas: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}