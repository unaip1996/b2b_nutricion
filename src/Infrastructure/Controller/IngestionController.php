<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Repository\DocumentChunkRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

readonly class IngestionController
{
    public function __construct(
        private DocumentChunkRepository $documentChunkRepository,
        private IngestClinicalDocumentUseCase $ingestUseCase
    ) {}

    #[Route('/api/ingest', name: 'api_ingest_document', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'Falta el archivo PDF en la petición.'], Response::HTTP_BAD_REQUEST);
        }

        // 🚨 NUEVA VALIDACIÓN: Comprueba si PHP bloqueó el archivo (ej. por exceso de tamaño)
        if (!$file->isValid()) {
            return new JsonResponse([
                'error' => 'El servidor rechazó el archivo. Causa: ' . $file->getErrorMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$file) {
            return new JsonResponse(['error' => 'Falta el archivo PDF.'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getMimeType() !== 'application/pdf') {
            return new JsonResponse(['error' => 'Formato no soportado. Debe ser PDF.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Ejecución global, sin ataduras a pacientes
            $this->ingestUseCase->execute($file->getPathname(), $file->getClientOriginalName());
            
            return new JsonResponse([
                'message' => 'Guía clínica indexada correctamente en la base de conocimiento global.'
            ], Response::HTTP_OK);
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error al procesar el documento: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/knowledge-base', name: 'api_knowledge_base_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listDocuments(): JsonResponse
    {
        $result = $this->documentChunkRepository->findGroupedDocuments();

        $documents = array_map(function (array $row) {
            // Si ingested_at es null, usamos la fecha actual o una por defecto
            $fechaStr = $row['ingested_at'] ?? 'now';
            // Si file_name es null, le ponemos un nombre genérico
            $nombre = $row['file_name'] ?? 'Documento Antiguo (Sin nombre)';

            return [
                'nombre' => $nombre,
                'fecha' => (new \DateTime($fechaStr))->format('d M Y, H:i'),
                'chunks' => $row['chunk_count'] . ' Chunks',
                'tipo' => 'Guía Clínica / Protocolo'
            ];
        }, $result);

        return new JsonResponse(['data' => $documents], Response::HTTP_OK);
    }

    #[Route('/api/knowledge-base/{fileName}', name: 'api_knowledge_base_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteDocument(Request $request, string $fileName): JsonResponse
    {

        try {
            // Decodificamos el nombre por si viene con espacios o tildes en la URL (%20, etc)
            $decodedFileName = urldecode($fileName);
            
            $this->documentChunkRepository->deleteByFileName($decodedFileName);

            return new JsonResponse([
                'message' => 'Documento y sus vectores eliminados de la base de conocimiento.'
            ], Response::HTTP_OK);
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error al eliminar el documento: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}