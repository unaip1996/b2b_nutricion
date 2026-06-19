<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Infrastructure\Entity\DietaryPlan;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

readonly class DietController
{
    public function __construct(
        private GenerateClinicalDietUseCase $generateClinicalDietUseCase,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/diets/generate', name: 'api_diets_generate', methods: ['POST'])]
    public function generateDiet(Request $request, GenerateClinicalDietUseCase $useCase): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $patientId = $data['patientId'] ?? null;
            $query = $data['query'] ?? null;
            // Extraemos los nuevos parámetros del JSON que envía tu frontend de Next.js
            $kcal = $data['kcal'] ?? 2000;
            $startDateStr = $data['startDate'] ?? date('Y-m-d');
            $endDateStr = $data['endDate'] ?? date('Y-m-d', strtotime('+30 days'));

            if (!$patientId || !$query) {
                return new JsonResponse(['error' => 'Faltan parámetros obligatorios.'], Response::HTTP_BAD_REQUEST);
            }

            // Parseamos los strings de fecha a objetos DateTimeImmutable que requiere Doctrine
            $startDate = new \DateTimeImmutable($startDateStr);
            $endDate = new \DateTimeImmutable($endDateStr);

            // ¡Ahora sí le pasamos los 5 parámetros esperados!
            $dietProposal = $useCase->execute($patientId, $query, (int)$kcal, $startDate, $endDate);

            return new JsonResponse([
                'data' => [
                    'dietary_proposal' => $dietProposal
                ]
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Ha ocurrido un error interno en el motor de IA. Detalles: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/patients/{patientId}/diets', name: 'api_patient_diets_list', methods: ['GET'])]
    public function listPatientDiets(string $patientId, EntityManagerInterface $em): JsonResponse
    {
        try {
            $diets = $em->getRepository(DietaryPlan::class)->findBy([
                'patient' => $patientId,
                'deletedAt' => null
            ]);

            $now = new \DateTimeImmutable();

            $data = array_map(function (DietaryPlan $plan) use ($now) {
                // LÓGICA DE ESTADO CALCULADO (Robusta para el TFG)
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

                return [
                    'id' => $plan->getId()->toRfc4122(),
                    'createdAt' => $plan->getStartDate() ? $plan->getStartDate()->format('Y-m-d') : date('Y-m-d'),
                    'kcal' => $plan->getKcal() ?? 2000, // Lee de la nueva columna de la BD
                    'status' => $status, // Estado dinámico real
                ];
            }, $diets);

            return new JsonResponse(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/diets/{id}', name: 'api_diet_delete', methods: ['DELETE'])]
    public function deleteDiet(string $id, EntityManagerInterface $em): JsonResponse
    {
        try {
            $diet = $em->getRepository(DietaryPlan::class)->find($id);

            if (!$diet) {
                return new JsonResponse(['error' => 'Pauta nutricional no encontrada.'], Response::HTTP_NOT_FOUND);
            }

            // Borrado lógico (Soft Delete)
            $diet->setDeletedAt(new \DateTimeImmutable());
            $em->flush();

            return new JsonResponse(['message' => 'Pauta eliminada correctamente.'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Fallo al eliminar: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}