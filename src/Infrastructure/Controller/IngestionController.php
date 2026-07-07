<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Repository\ClinicalDocumentRepository;
use App\Infrastructure\Repository\DocumentChunkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

readonly class IngestionController
{
    public function __construct(
        private DocumentChunkRepository $documentChunkRepository,
        private IngestClinicalDocumentUseCase $ingestUseCase,
        private ClinicalDocumentRepository $documentRepository
    ) {}

    #[Route('/api/ingest', name: 'api_ingest_document', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
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

        if (!$file) {
            return new JsonResponse(['error' => 'Falta el archivo PDF.'], Response::HTTP_BAD_REQUEST);
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
    public function deleteDocument(string $id, EntityManagerInterface $entityManager): JsonResponse
    {

        try {
            $document = $entityManager->getRepository(ClinicalDocument::class)->find($id);

            // Verificamos si el documento no existe o si ya ha sido borrado lógicamente
            if (!$document || $document->getDeletedAt() !== null) {
                return new JsonResponse(['error' => 'Documento no encontrado o ya eliminado'], Response::HTTP_NOT_FOUND);
            }

            $now = new \DateTimeImmutable();

            foreach ($document->getChunks() as $chunk) {
                if (method_exists($chunk, 'setDeletedAt')) {
                    $chunk->setDeletedAt($now);
                }
            }

            // 2. SOFT DELETE DEL PADRE: Marcamos el documento clínico como eliminado
            $document->setDeletedAt($now);

            // Confirmamos los cambios de forma atómica en PostgreSQL
            $entityManager->flush();
        } catch (\Exception $e) {
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Error al eliminar el documento: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'success', 'message' => 'Documento y fragmentos eliminados'], Response::HTTP_OK);
    }
}