<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\EmbeddingGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use RuntimeException;
use Throwable;

class OpenAiEmbeddingAdapter implements EmbeddingGeneratorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private string $openAiApiKey 
    ) {}

    public function generateEmbedding(string $text): array
    {
        try {
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
                throw new RuntimeException('Error al comunicarse con la API de OpenAI (Embeddings): ' . $response->getContent(false));
            }

            $data = $response->toArray();

            if (!isset($data['data'][0]['embedding'])) {
                throw new RuntimeException('Estructura de respuesta de OpenAI inválida.');
            }

            return $data['data'][0]['embedding'];

        } catch (Throwable $e) {
            throw new RuntimeException('Fallo crítico en la generación del vector: ' . $e->getMessage(), 0, $e);
        }
    }
}