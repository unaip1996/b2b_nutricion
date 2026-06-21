<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\EmbeddingGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OpenAiEmbeddingAdapter implements EmbeddingGeneratorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private string $openAiApiKey 
    ) {}

    // 🔴 PROD: LLAMADA REAL A OPENAI (COMENTADA POR FALTA DE SALDO)
    /*
    public function generateEmbedding(string $text): array
    {
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
    */

    // 🟢 DEV: SIMULACRO PARA PODER DESARROLLAR (ACTIVO)
    public function generateEmbedding(string $text): array
    {
        // Devuelve un array de 1536 números aleatorios entre -1 y 1
        // Simula perfectamente lo que devolvería text-embedding-3-small
        $vector = [];
        for ($i = 0; $i < 1536; $i++) {
            $vector[] = (mt_rand() / mt_getrandmax()) * 2 - 1;
        }
        return $vector;
    }
}