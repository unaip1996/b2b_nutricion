<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use App\Infrastructure\Repository\DocumentChunkRepository;
use App\Infrastructure\Repository\PatientRepository;
use App\Infrastructure\Entity\DietaryPlan;
use App\Infrastructure\Entity\DietDay;
use App\Infrastructure\Entity\Meal;
use App\Infrastructure\Entity\MealItem;
use App\Infrastructure\Entity\FoodItem;
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

        // 3. Recuperar fragmentos desde la base de conocimiento vectorial (RAG)
        $chunkEntities = $this->documentChunkRepository->findSimilarChunkEntities($queryVectorString, 4);

        $contextTexts = [];
        foreach ($chunkEntities as $entity) {
            $contextTexts[] = $entity->getContent();
        }
        $contextString = implode("\n\n---\n\n", $contextTexts);

        // 4. Prompting
        $systemPrompt = "Eres un asistente clínico experto en nutrición. Utiliza EXCLUSIVAMENTE el siguiente contexto médico indexado para fundamentar tu propuesta nutricional. \n\nContexto Médico:\n" . $contextString;
        
        // 5. Inferencia (El LLM ahora devuelve un JSON estricto)
        $dietContent = $this->llmInference->generateText($systemPrompt, $query);
        
        // 6. Decodificar la salida estructurada
        $dietData = json_decode($dietContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("La IA no devolvió un JSON válido. Error: " . json_last_error_msg());
        }

        // 7. HIDRATACIÓN DEL DOMINIO B2B (Instanciamos la red de entidades)
        $dietaryPlan = new DietaryPlan();
        $dietaryPlan->setPatient($patient);
        $dietaryPlan->setName("Plan Nutricional RAG - " . ($dietData['totalKcal'] ?? $kcal) . " kcal");
        $dietaryPlan->setObservations($dietData['observations'] ?? 'Dieta generada por IA.');
        $dietaryPlan->setKcal($dietData['totalKcal'] ?? $kcal);
        $dietaryPlan->setStartDate($startDate);
        $dietaryPlan->setEndDate($endDate);
        
        foreach ($chunkEntities as $entity) {
            $dietaryPlan->addDocumentChunk($entity);
        }

        $foodRepo = $this->em->getRepository(FoodItem::class);

        // Iteramos los Días
        foreach ($dietData['days'] as $dayData) {
            $dietDay = new DietDay();
            $dietDay->setDayNumber($dayData['dayNumber']);
            $dietaryPlan->addDietDay($dietDay);

            // Iteramos las Comidas
            foreach ($dayData['meals'] as $mealData) {
                $meal = new Meal();
                $meal->setName($mealData['type']);
                try {
                    $meal->setMealTime(new \DateTimeImmutable($mealData['time']));
                } catch (\Exception $e) {
                    $meal->setMealTime(null);
                }
                $dietDay->addMeal($meal);

                // Iteramos los Alimentos
                foreach ($mealData['items'] as $itemData) {
                    $foodName = $itemData['foodName'] ?? 'Alimento Desconocido';
                    
                    // Comprobamos si el alimento ya existe en la base de datos de la clínica
                    $foodItem = $foodRepo->findOneBy(['name' => $foodName]);
                    if (!$foodItem) {
                        $foodItem = new FoodItem();
                        $foodItem->setName($foodName);
                        $foodItem->setKcalPer100g((float) ($itemData['kcal'] ?? 0));
                        $foodItem->setMacros([
                            'proteins' => $itemData['proteins'] ?? 0,
                            'carbs' => $itemData['carbs'] ?? 0,
                            'fats' => $itemData['fats'] ?? 0,
                        ]);
                        $foodItem->setCategory('Generado por IA');
                        $this->em->persist($foodItem); // Este persist manual es obligatorio porque no hay cascada desde MealItem
                    }

                    $mealItem = new MealItem();
                    $mealItem->setFoodItem($foodItem);

                    // Separamos inteligentemente el string "150g" o "1 taza" que devuelve OpenAI
                    // Regex captura el número y luego la unidad
                    preg_match('/([\d.,]+)\s*(.*)/', $itemData['quantity'] ?? '1 ud', $matches);
                    $qty = isset($matches[1]) ? (float) str_replace(',', '.', $matches[1]) : 1.0;
                    $unit = !empty($matches[2]) ? $matches[2] : 'ud';

                    $mealItem->setQuantity($qty);
                    $mealItem->setUnit($unit);
                    
                    $meal->addMealItem($mealItem);
                }
            }
        }

        // 8. Persistencia Atómica (Guarda Días, Comidas y Elementos por cascada)
        $this->em->persist($dietaryPlan);
        $this->em->flush();

        // Devolvemos el JSON crudo al frontend para que pueda renderizarlo o parsearlo inmediatamente
        return $dietContent; 
    }
}