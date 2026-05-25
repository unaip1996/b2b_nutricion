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
use Throwable;

readonly class DietController
{
    public function __construct(
        private GenerateClinicalDietUseCase $generateClinicalDietUseCase,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/diets/generate', name: 'api_diets_generate', methods: ['POST'])]
    public function generateDiet(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            
            $query = $payload['query'] ?? null;

            if (!is_string($query) || trim($query) === '') {
                return new JsonResponse(
                    ['error' => 'El parámetro "query" es obligatorio y debe ser un texto válido.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Asumimos que el Use Case expone un método execute(), acorde a los estándares de CQRS/UseCases.
            $result = $this->generateClinicalDietUseCase->execute(trim($query));

            return new JsonResponse(
                ['data' => ['dietary_proposal' => $result]],
                Response::HTTP_OK
            );
        } catch (JsonException $e) {
            return new JsonResponse(
                ['error' => 'El cuerpo de la petición debe ser un JSON válido.'],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Throwable $e) {
            $this->logger->error('Error interno al generar dieta clínica: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return new JsonResponse(
                ['error' => 'Ha ocurrido un error interno en el servidor. Por favor, inténtelo más tarde.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
