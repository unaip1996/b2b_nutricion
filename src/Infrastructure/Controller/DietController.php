<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use App\Infrastructure\Entity\DietDay;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\FoodItem;
use App\Infrastructure\Entity\Meal;
use App\Infrastructure\Entity\MealItem;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[OA\Tag(name: 'Dietas y Motor RAG', description: 'Endpoints para la generación de dietas mediante Inteligencia Artificial y gestión de pautas')]
readonly class DietController
{
    public function __construct(
        private GenerateClinicalDietUseCase $generateClinicalDietUseCase,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/diets/generate', name: 'api_diets_generate', methods: ['POST'])]
    #[OA\Post(summary: 'Genera una pauta nutricional estructurada utilizando el Motor RAG')]
    #[OA\RequestBody(
        required: true,
        description: 'Parámetros clínicos y petición en lenguaje natural para la IA',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'patientId', type: 'string', description: 'UUID del paciente'),
                new OA\Property(property: 'query', type: 'string', example: 'Dieta baja en FODMAPs, enfocada en ganar masa muscular'),
                new OA\Property(property: 'kcal', type: 'integer', example: 2500),
                new OA\Property(property: 'startDate', type: 'string', format: 'date', example: '2026-07-08'),
                new OA\Property(property: 'endDate', type: 'string', format: 'date', example: '2026-08-08')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Dieta generada exitosamente. Devuelve el JSON estructurado por OpenAI',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data', 
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'dietary_proposal', type: 'string', description: 'String JSON con la dieta estructurada')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Faltan parámetros obligatorios')]
    #[OA\Response(response: 500, description: 'Error interno en el motor de inferencia (LLM)')]
    public function generateDiet(Request $request, GenerateClinicalDietUseCase $useCase): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $patientId = $data['patientId'] ?? null;
            $query = $data['query'] ?? null;
            $kcal = $data['kcal'] ?? 2000;
            $startDateStr = $data['startDate'] ?? date('Y-m-d');
            $endDateStr = $data['endDate'] ?? date('Y-m-d', strtotime('+30 days'));

            if (!$patientId || !$query) {
                return new JsonResponse(['error' => 'Faltan parámetros obligatorios.'], Response::HTTP_BAD_REQUEST);
            }

            $startDate = new \DateTimeImmutable($startDateStr);
            $endDate = new \DateTimeImmutable($endDateStr);

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
    #[OA\Get(summary: 'Lista el historial de dietas generadas para un paciente específico')]
    #[OA\Parameter(name: 'patientId', description: 'UUID del paciente', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Array con el listado de dietas del paciente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date'),
                            new OA\Property(property: 'kcal', type: 'integer'),
                            new OA\Property(property: 'status', type: 'string', example: 'Activo')
                        ]
                    )
                )
            ]
        )
    )]
    public function listPatientDiets(string $patientId, EntityManagerInterface $em): JsonResponse
    {
        try {
            $diets = $em->getRepository(DietaryPlan::class)->findBy([
                'patient' => $patientId,
                'deletedAt' => null
            ]);

            $now = new \DateTimeImmutable();

            $data = array_map(function (DietaryPlan $plan) use ($now) {
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
                    'name' => $plan->getName() ?? '',
                    'createdAt' => $plan->getStartDate() ? $plan->getStartDate()->format('Y-m-d') : date('Y-m-d'),
                    'kcal' => $plan->getKcal() ?? 2000, 
                    'status' => $status, 
                ];
            }, $diets);

            return new JsonResponse(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/diets/{id}', name: 'api_diet_detail', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(summary: 'Obtiene el detalle completo de una pauta nutricional para su edición')]
    #[OA\Parameter(name: 'id', description: 'UUID de la dieta', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Datos detallados de la dieta')]
    #[OA\Response(response: 404, description: 'Pauta nutricional no encontrada')]
    public function getDietDetail(string $id, EntityManagerInterface $em): JsonResponse
    {
        try {
            $diet = $em->getRepository(DietaryPlan::class)->find($id);

            if (!$diet) {
                return new JsonResponse(['error' => 'Pauta nutricional no encontrada.'], Response::HTTP_NOT_FOUND);
            }

            $daysData = [];
            foreach ($diet->getDietDays() as $day) {
                $mealsData = [];
                foreach ($day->getMeals() as $meal) {
                    $itemsData = [];
                    foreach ($meal->getMealItems() as $item) {
                        $itemsData[] = [
                            'id' => $item->getId()->toRfc4122(),
                            'foodItemId' => $item->getFoodItem()->getId()->toRfc4122(),
                            'foodName' => $item->getFoodItem()->getName(),
                            'quantity' => $item->getQuantity(),
                            'unit' => $item->getUnit(),
                        ];
                    }
                    $mealsData[] = [
                        'id' => $meal->getId()->toRfc4122(),
                        'name' => $meal->getName(),
                        'mealTime' => $meal->getMealTime() ? $meal->getMealTime()->format('H:i') : '00:00',
                        'items' => $itemsData,
                    ];
                }
                $daysData[] = [
                    'id' => $day->getId()->toRfc4122(),
                    'dayNumber' => $day->getDayNumber(),
                    'meals' => $mealsData,
                ];
            }

            $data = [
                'id' => $diet->getId()->toRfc4122(),
                'name' => $diet->getName(),
                'kcal' => $diet->getKcal(),
                'startDate' => $diet->getStartDate() ? $diet->getStartDate()->format('Y-m-d') : null,
                'endDate' => $diet->getEndDate() ? $diet->getEndDate()->format('Y-m-d') : null,
                'observations' => $diet->getObservations(),
                'days' => $daysData,
                'patient' => $diet->getPatient() ? [
                    'name' => $diet->getPatient()->getName()
                ] : null,
            ];

            return new JsonResponse(['data' => $data], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Error fetching diet detail: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Error al obtener el detalle de la dieta: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/diets/{id}', name: 'api_diet_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Put(summary: 'Actualiza una pauta nutricional existente')]
    #[OA\Parameter(name: 'id', description: 'UUID de la dieta a modificar', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(description: 'Objeto completo de la dieta con las modificaciones', required: true)]
    #[OA\Response(response: 200, description: 'Pauta actualizada con éxito')]
    #[OA\Response(response: 404, description: 'Pauta nutricional no encontrada')]
    public function updateDiet(string $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $diet = $em->getRepository(DietaryPlan::class)->find($id);

            if (!$diet) {
                return new JsonResponse(['error' => 'Pauta nutricional no encontrada.'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            $diet->setKcal((int)($data['kcal'] ?? $diet->getKcal()));
            $diet->setObservations($data['observations'] ?? $diet->getObservations());
            if (isset($data['startDate'])) {
                $diet->setStartDate(new \DateTimeImmutable($data['startDate']));
            }
            if (isset($data['endDate'])) {
                $diet->setEndDate(new \DateTimeImmutable($data['endDate']));
            }
            if (isset($data['name'])) {
                $diet->setName($data['name']);
            }

            $diet->getDietDays()->clear();
            $em->flush(); 

            $foodRepo = $em->getRepository(FoodItem::class);

            foreach ($data['days'] as $dayData) {
                $dietDay = new DietDay();
                $dietDay->setDayNumber($dayData['dayNumber']);
                $diet->addDietDay($dietDay);

                foreach ($dayData['meals'] as $mealData) {
                    $meal = new Meal();
                    $meal->setName($mealData['name']);
                    $meal->setMealTime(isset($mealData['mealTime']) ? new \DateTimeImmutable($mealData['mealTime']) : null);
                    $dietDay->addMeal($meal);

                    foreach ($mealData['items'] as $itemData) {
                        $foodName = $itemData['foodName'] ?? 'Alimento Desconocido';
                        $foodItem = $foodRepo->findOneBy(['name' => $foodName]) ?? (new FoodItem())->setName($foodName)->setCategory('Editado Manualmente');
                        $em->persist($foodItem);

                        $mealItem = new MealItem();
                        $mealItem->setFoodItem($foodItem)->setQuantity((float)($itemData['quantity'] ?? 1.0))->setUnit($itemData['unit'] ?? 'ud');
                        $meal->addMealItem($mealItem);
                    }
                }
            }

            $em->persist($diet);
            $em->flush();

            return new JsonResponse(['message' => 'Pauta actualizada con éxito.'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Error updating diet: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return new JsonResponse(['error' => 'Error al actualizar la pauta: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/diets/{id}', name: 'api_diet_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Elimina lógicamente (Soft Delete) una pauta nutricional')]
    #[OA\Parameter(name: 'id', description: 'UUID de la dieta a eliminar', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Pauta eliminada correctamente')]
    #[OA\Response(response: 404, description: 'Pauta nutricional no encontrada')]
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