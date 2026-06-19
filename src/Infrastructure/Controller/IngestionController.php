<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Entity\User;
use App\Infrastructure\Repository\PatientRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

readonly class IngestionController
{
    public function __construct(
        private PatientRepository $patientRepository,
        private AuthorizationCheckerInterface $authChecker,
        private TokenStorageInterface $tokenStorage,
        private IngestClinicalDocumentUseCase $ingestUseCase
    ) {}

    #[Route('/api/ingest', name: 'api_ingest_document', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function upload(Request $request): JsonResponse
    {
        // Al subir archivos, se usa form-data, no JSON plano
        $patientId = $request->request->get('patientId');
        $file = $request->files->get('file');

        if (!$patientId || !$file) {
            return new JsonResponse(['error' => 'Falta el ID del paciente o el archivo PDF.'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getMimeType() !== 'application/pdf') {
            return new JsonResponse(['error' => 'Formato no soportado. El archivo debe ser un PDF.'], Response::HTTP_BAD_REQUEST);
        }

        $patient = $this->patientRepository->find($patientId);
        if (!$patient || $patient->getDeletedAt() !== null) {
            return new JsonResponse(['error' => 'Paciente no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        // 🛡️ PROTECCIÓN B2B MULTITENANT
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()?->getUser();
            $profile = $user ? $user->getNutritionistProfile() : null;
            
            if ($patient->getNutritionistProfile() !== $profile) {
                return new JsonResponse(['error' => 'Acceso denegado: Este paciente pertenece a otra clínica.'], Response::HTTP_FORBIDDEN);
            }
        }

        try {
            // Ejecutamos el motor RAG
            $this->ingestUseCase->execute($patient, $file->getPathname(), $file->getClientOriginalName());
            
            return new JsonResponse([
                'message' => 'Documento procesado, vectorizado e indexado correctamente en la base de conocimiento.'
            ], Response::HTTP_OK);
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error en el motor de IA al procesar el documento: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}