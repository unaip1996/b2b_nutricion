<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use App\Infrastructure\Repository\DocumentChunkRepository;
use App\Infrastructure\Repository\PatientRepository;
use App\Infrastructure\Entity\DietaryPlan;
use Doctrine\ORM\EntityManagerInterface;

readonly class GenerateClinicalDietUseCase
{
    public function __construct(
        private PatientRepository $patientRepository,
        private DocumentChunkRepository $documentChunkRepository,
        private EmbeddingGeneratorInterface $embeddingGenerator,
        private LlmInferenceInterface $llmInference,
        private EntityManagerInterface $em
    ) {}

    public function execute(
        string $patientId, 
        string $query, 
        int $kcal, 
        \DateTimeImmutable $startDate, 
        \DateTimeImmutable $endDate
    ): string {
        // 1. Obtener contexto del paciente
        $patient = $this->patientRepository->find($patientId);
        if (!$patient) {
            throw new \InvalidArgumentException("Paciente no localizado.");
        }

        // 2. Vectorizar la petición del frontend
        $queryVectorArray = $this->embeddingGenerator->generateEmbedding($query);
        $queryVectorString = json_encode($queryVectorArray); 

        // 3. Recuperar ENTIDADES desde la base de conocimiento vectorial (RAG)
        $chunkEntities = $this->documentChunkRepository->findSimilarChunkEntities($queryVectorString, 4);

        // 4. Extraer el texto plano para construir el contexto del LLM
        $contextTexts = [];
        foreach ($chunkEntities as $entity) {
            $contextTexts[] = $entity->getContent();
        }
        $contextString = implode("\n\n---\n\n", $contextTexts);

        // 5. Definir las variables de Prompting que faltaban
        $systemPrompt = "Eres un asistente clínico experto en nutrición. Utiliza EXCLUSIVAMENTE el siguiente contexto médico indexado para fundamentar tu propuesta nutricional. Si el contexto no es suficiente, indícalo. \n\nContexto Médico:\n" . $contextString;
        $userPrompt = $query;

        // 6. Ejecutar la inferencia (Simulada o en API real)
        $dietContent = $this->llmInference->generateText($systemPrompt, $userPrompt);

        // 7. Persistencia con Trazabilidad (B2B RAG)
        $dietPlan = new DietaryPlan();
        $dietPlan->setPatient($patient);
        $dietPlan->setName("Plan Nutricional RAG - " . $kcal . " kcal");
        $dietPlan->setKcal($kcal);
        $dietPlan->setStartDate($startDate);
        $dietPlan->setEndDate($endDate);
        
        // Guardamos la trazabilidad de qué fragmentos se usaron
        foreach ($chunkEntities as $entity) {
            $dietPlan->addDocumentChunk($entity);
        }

        $this->em->persist($dietPlan);
        $this->em->flush();

        return $dietContent;
    }
}