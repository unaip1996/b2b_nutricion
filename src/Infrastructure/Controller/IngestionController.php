<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Repository\ClinicalDocumentRepository;
use App\Infrastructure\Repository\DocumentChunkRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Base de Conocimiento (RAG)', description: 'Endpoints para la ingesta, indexación y gestión de documentos clínicos (PDFs)')]
readonly class IngestionController
{
    public function __construct(
        private DocumentChunkRepository $documentChunkRepository,
        private IngestClinicalDocumentUseCase $ingestUseCase,
        private ClinicalDocumentRepository $documentRepository
    ) {}

    #[Route('/api/ingest', name: 'api_ingest_document', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(summary: 'Sube, extrae texto (OCR/Nativo) y vectoriza un documento clínico (PDF)')]
    #[OA\RequestBody(
        description: 'Formulario multipart con el archivo PDF a procesar',
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: 'file', 
                        description: 'El archivo PDF clínico', 
                        type: 'string', 
                        format: 'binary'
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200, 
        description: 'Documento indexado correctamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Guía clínica indexada correctamente en la base de conocimiento global.')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Petición inválida, falta archivo o no es un formato soportado (PDF)')]
    #[OA\Response(response: 500, description: 'Error en la extracción OCR o en el motor de embeddings')]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'Falta el archivo PDF en la petición.'], Response::HTTP_BAD_REQUEST);
        }

        // Comprueba si PHP bloqueó el archivo (ej. por exceso de tamaño)
        if (!$file->isValid()) {
            return new JsonResponse([
                'error' => 'El servidor rechazó el archivo. Causa: ' . $file->getErrorMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getMimeType() !== 'application/pdf') {
            return new JsonResponse(['error' => 'Formato no soportado. Debe ser PDF.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Ejecución global, sin ataduras a pacientes
            $this->ingestUseCase->execute($file);
            
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
    #[OA\Get(summary: 'Lista todos los documentos clínicos activos y disponibles para el LLM')]
    #[OA\Response(
        response: 200,
        description: 'Array con el listado de documentos indexados',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string', example: '123e4567-e89b-12d3-a456-426614174000'),
                    new OA\Property(property: 'title', type: 'string', example: 'guia_obesidad_2025.pdf'),
                    new OA\Property(property: 'chunksCount', type: 'integer', description: 'Número de fragmentos vectoriales generados', example: 45),
                    new OA\Property(property: 'uploadedAt', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'status', type: 'string', example: 'indexed')
                ]
            )
        )
    )]
    public function listDocuments(): JsonResponse
    {
        try {
            // Buscamos todos los documentos clínicos indexados
            $documents = $this->documentRepository->findAllActive();

            $data = [];
            foreach ($documents as $doc) {
                $data[] = [
                    'id' => $doc->getId()->toString(),
                    'title' => $doc->getFileName(),
                    'chunksCount' => $doc->getChunks()->count(),
                    'uploadedAt' => $doc->getIngestedAt()->format(\DateTimeInterface::ATOM),
                    'status' => 'indexed',
                ];
            }

            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error al recuperar la base de conocimiento',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/knowledge-base/{id}', name: 'api_knowledge_base_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Delete(summary: 'Elimina lógicamente (Soft Delete) un documento y oculta sus vectores')]
    #[OA\Parameter(name: 'id', description: 'UUID del documento a eliminar', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Documento y fragmentos eliminados correctamente')]
    #[OA\Response(response: 404, description: 'Documento no encontrado o ya eliminado')]
    public function deleteDocument(string $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $document = $entityManager->getRepository(ClinicalDocument::class)->find($id);

            // Verificamos si el documento no existe o si ya ha sido borrado lógicamente
            if (!$document || $document->getDeletedAt() !== null) {
                return new JsonResponse(['error' => 'Documento no encontrado o ya eliminado'], Response::HTTP_NOT_FOUND);
            }

            $now = new \DateTimeImmutable();

            // 1. SOFT DELETE CASCADA: Marcamos los fragmentos como eliminados
            foreach ($document->getChunks() as $chunk) {
                if (method_exists($chunk, 'setDeletedAt')) {
                    $chunk->setDeletedAt($now);
                }
            }

            // 2. SOFT DELETE DEL PADRE: Marcamos el documento clínico como eliminado
            $document->setDeletedAt($now);

            // Confirmamos los cambios de forma atómica en PostgreSQL
            $entityManager->flush();
            
            return new JsonResponse(['status' => 'success', 'message' => 'Documento y fragmentos eliminados'], Response::HTTP_OK);
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error al eliminar el documento: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}