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

        // --- NUEVO: EXTRACCIÓN DEL PERFIL CLÍNICO ---
        $allergies = $patient->getAllergies()->map(fn($a) => $a->getName())->toArray();
        $allergiesStr = empty($allergies) ? "Ninguna conocida" : implode(', ', $allergies);
        
        $pathologies = $patient->getPathologies() ?: "Ninguna registrada";
        
        // Obtenemos la última medición biométrica si existe
        $latestMeasurement = $patient->getMeasurements()->last();
        $biometricsStr = "No hay mediciones registradas.";
        if ($latestMeasurement) {
            $biometricsStr = sprintf(
                "Peso: %s kg, Altura: %s cm, Grasa: %s%%", 
                $latestMeasurement->getWeight() ?? 'N/A',
                $latestMeasurement->getHeight() ?? 'N/A',
                $latestMeasurement->getBodyFatPercentage() ?? 'N/A'
            );
        }

        // 2. Vectorizar la petición
        $queryVectorArray = $this->embeddingGenerator->generateEmbedding($query);
        $queryVectorString = json_encode($queryVectorArray); 

        // 3. Recuperar fragmentos (RAG)
        $chunkEntities = $this->documentChunkRepository->findSimilarChunkEntities($queryVectorString, 4);

        $contextTexts = [];
        foreach ($chunkEntities as $entity) {
            $contextTexts[] = $entity->getContent();
        }
        $contextString = implode("\n\n---\n\n", $contextTexts);

        // 4. PROMPTING CLÍNICO DEFENSIVO
        $systemPrompt = <<<PROMPT
Eres un asistente clínico experto en nutrición. Utiliza EXCLUSIVAMENTE el siguiente contexto médico indexado para fundamentar tu propuesta.

CONTEXTO MÉDICO (Base de Conocimiento):
$contextString

PERFIL DEL PACIENTE:
- Alergias/Intolerancias: $allergiesStr
- Patologías Clínicas: $pathologies
- Biometría Actual: $biometricsStr
- Objetivo Kcal Diarias: $kcal kcal

REGLA CRÍTICA DE SEGURIDAD: 
Bajo NINGUNA circunstancia puedes incluir alimentos que contengan o deriven de los alérgenos mencionados. 
PROMPT;
        
        // 5. Inferencia (Structured Outputs)
        $dietContent = $this->llmInference->generateText($systemPrompt, $query);
        
        // 6. Decodificar JSON
        $dietData = json_decode($dietContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("La IA no devolvió un JSON válido. Error: " . json_last_error_msg());
        }

        // 7. HIDRATACIÓN Y VALIDACIÓN PROGRAMÁTICA DE SEGURIDAD
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

        foreach ($dietData['days'] as $dayData) {
            $dietDay = new DietDay();
            $dietDay->setDayNumber($dayData['dayNumber']);
            $dietaryPlan->addDietDay($dietDay);

            foreach ($dayData['meals'] as $mealData) {
                $meal = new Meal();
                $meal->setName($mealData['type']);
                try {
                    $meal->setMealTime(new \DateTimeImmutable($mealData['time']));
                } catch (\Exception $e) {
                    $meal->setMealTime(null);
                }
                $dietDay->addMeal($meal);

                foreach ($mealData['items'] as $itemData) {
                    $foodName = $itemData['foodName'] ?? 'Alimento Desconocido';
                    
                    // --- NUEVO: FILTRO PROGRAMÁTICO ANTI-ALERGIAS ---
                    foreach ($allergies as $allergy) {
                        // Búsqueda insensible a mayúsculas/minúsculas
                        if (stripos($foodName, $allergy) !== false) {
                            throw new \RuntimeException(sprintf(
                                "ALERTA DE SEGURIDAD CLÍNICA: La IA intentó recetar '%s', lo cual entra en conflicto con la alergia del paciente a '%s'. Generación abortada.",
                                $foodName,
                                $allergy
                            ));
                        }
                    }

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
                        $this->em->persist($foodItem); 
                    }

                    $mealItem = new MealItem();
                    $mealItem->setFoodItem($foodItem);

                    preg_match('/([\d.,]+)\s*(.*)/', $itemData['quantity'] ?? '1 ud', $matches);
                    $qty = isset($matches[1]) ? (float) str_replace(',', '.', $matches[1]) : 1.0;
                    $unit = !empty($matches[2]) ? $matches[2] : 'ud';

                    $mealItem->setQuantity($qty);
                    $mealItem->setUnit($unit);
                    
                    $meal->addMealItem($mealItem);
                }
            }
        }

        $this->em->persist($dietaryPlan);
        $this->em->flush();

        return $dietContent; 
    }
}