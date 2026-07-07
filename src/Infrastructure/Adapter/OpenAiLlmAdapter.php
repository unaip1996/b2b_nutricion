<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\LlmInferenceInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

readonly class OpenAiLlmAdapter implements LlmInferenceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private string $apiKey,
    ) {
    }

    public function generateText(string $systemPrompt, string $userPrompt): string 
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'gpt-4o-mini',
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role'    => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.0,
                    
                    // Forzamos la salida estructurada para hidratar las entidades del dominio
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'dietary_plan_schema',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'observations' => [
                                        'type' => 'string',
                                        'description' => 'Justificación clínica de la dieta basada en los PDFs.'
                                    ],
                                    'totalKcal' => [
                                        'type' => 'integer',
                                        'description' => 'Calorías totales diarias estimadas promedio.'
                                    ],
                                    'days' => [
                                        'type' => 'array',
                                        'description' => 'Los días que componen la pauta dietética (ej. 1 al 7).',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'dayNumber' => ['type' => 'integer'],
                                                'meals' => [
                                                    'type' => 'array',
                                                    'items' => [
                                                        'type' => 'object',
                                                        'properties' => [
                                                            'type' => [
                                                                'type' => 'string',
                                                                'description' => 'Ej: Desayuno, Media Mañana, Comida, Merienda, Cena'
                                                            ],
                                                            'time' => ['type' => 'string', 'description' => 'Hora recomendada, ej: 08:30'],
                                                            'items' => [
                                                                'type' => 'array',
                                                                'items' => [
                                                                    'type' => 'object',
                                                                    'properties' => [
                                                                        'foodName' => ['type' => 'string'],
                                                                        'quantity' => ['type' => 'string', 'description' => 'Ej: 150g, 1 taza'],
                                                                        'kcal' => ['type' => 'integer'],
                                                                        'proteins' => ['type' => 'number'],
                                                                        'carbs' => ['type' => 'number'],
                                                                        'fats' => ['type' => 'number']
                                                                    ],
                                                                    'required' => ['foodName', 'quantity', 'kcal', 'proteins', 'carbs', 'fats'],
                                                                    'additionalProperties' => false
                                                                ]
                                                            ]
                                                        ],
                                                        'required' => ['type', 'time', 'items'],
                                                        'additionalProperties' => false
                                                    ]
                                                ]
                                            ],
                                            'required' => ['dayNumber', 'meals'],
                                            'additionalProperties' => false
                                        ]
                                    ]
                                ],
                                'required' => ['observations', 'totalKcal', 'days'],
                                'additionalProperties' => false
                            ]
                        ]
                    ]
                ],
            ]);

            $data = $response->toArray(); 
            $content = $data['choices'][0]['message']['content'] ?? null;
            
            if ($content === null) {
                throw new RuntimeException('La respuesta de OpenAI no contiene el texto generado esperado.');
            }
            
            return (string) $content;

        } catch (HttpExceptionInterface $e) {
            $errorBody = $e->getResponse()->getContent(false);
            throw new RuntimeException(sprintf("Error de OpenAI HTTP %d: %s", $e->getResponse()->getStatusCode(), $errorBody));
        } catch (Throwable $e) {
            throw new RuntimeException('Fallo general en la infraestructura del LLM: ' . $e->getMessage(), 0, $e);
        }
    }
}