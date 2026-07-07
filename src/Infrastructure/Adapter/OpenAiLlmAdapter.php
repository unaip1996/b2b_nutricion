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
                    // Temperatura 0 para priorizar el determinismo clínico (RAG)
                    'temperature' => 0.0, 
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