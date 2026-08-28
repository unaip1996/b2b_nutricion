<?php
declare(strict_types=1);

namespace App\Tests\Stub;

use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;

class MockOpenAiServices implements EmbeddingGeneratorInterface, LlmInferenceInterface
{
    public function generateEmbedding(string $text): array
    {
        return [0.1, 0.2, 0.3]; // Vector falso para que pgvector no falle
    }

    public function generateText(string $prompt, string $userPrompt): string
    {
        // JSON perfecto simulando la respuesta de la IA
        return json_encode([
            'totalKcal' => 2000,
            'observations' => 'Dieta generada por entorno de test',
            'days' => [
                [
                    'dayNumber' => 1,
                    'meals' => [
                        [
                            'type' => 'Desayuno',
                            'time' => '08:00',
                            'items' => [
                                ['foodName' => 'Avena', 'kcal' => 100, 'proteins' => 5, 'carbs' => 20, 'fats' => 2, 'quantity' => '50g']
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}