<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\EmbeddingGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Adaptador de Infraestructura para generar embeddings utilizando la API de OpenAI.
 */
readonly class OpenAiEmbeddingAdapter implements EmbeddingGeneratorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
       #[Autowire(env: 'OPENAI_API_KEY')]
        private string $openAiApiKey 
    ) {}

    public function generateEmbedding(string $text): array
    {
        // Utilizamos el modelo recomendado por OpenAI para búsquedas RAG eficientes
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openAiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'input' => $text,
                'model' => 'text-embedding-3-small', 
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Error al comunicarse con la API de OpenAI: ' . $response->getContent(false));
        }

        $data = $response->toArray();

        return $data['data'][0]['embedding'];
    }

    // PROD
    // public function generate(string $text): array
    // {
    //     $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
    //         'headers' => [
    //             'Authorization' => 'Bearer ' . $this->openAiApiKey,
    //             'Content-Type' => 'application/json',
    //         ],
    //         'json' => [
    //             'input' => $text,
    //             'model' => 'text-embedding-3-small',
    //         ],
    //     ]);

    //     $data = $response->toArray();

    //     return $data['data'][0]['embedding'] ?? [];
    // }

    // DEV
    public function generate(string $text): array
    {
        // SIMULACRO temporal para saltar el error 429 de OpenAI
        // Devuelve un array de 1536 números aleatorios entre -1 y 1
        $vector = [];
        for ($i = 0; $i < 1536; $i++) {
            $vector[] = (mt_rand() / mt_getrandmax()) * 2 - 1;
        }
        return $vector;
    }
}