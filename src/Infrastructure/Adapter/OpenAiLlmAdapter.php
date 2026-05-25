<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\LlmInferenceInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class OpenAiLlmAdapter implements LlmInferenceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
    ) {
    }

    public function generateResponse(string $prompt, array $context): string
    {
        // 🛠️ MODO DESARROLLO: Descomenta este método para probar de forma local y gratuita
        return $this->generateSimulatedResponse($prompt, $context);

        /*
        // 🚀 MODO PRODUCCIÓN: Descomenta este bloque cuando la cuenta de OpenAI tenga saldo
        $joinedContext = implode("\n\n", $context);

        $systemPrompt = "Eres un asistente clínico nutricional altamente cualificado. "
            . "Tu objetivo es proporcionar recomendaciones y generar dietas basándote ÚNICA Y EXCLUSIVAMENTE "
            . "en el contexto médico y biométrico proporcionado a continuación. "
            . "Bajo ninguna circunstancia debes inventar información ni utilizar conocimientos externos. "
            . "Si el contexto no contiene información suficiente para responder a la consulta de forma segura, indícalo claramente.\n\n"
            . "CONTEXTO RECUPERADO:\n"
            . $joinedContext;

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
                            'content' => $prompt,
                        ],
                    ],
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
            throw new RuntimeException(sprintf("Error de OpenAI (HTTP %d): %s", $e->getResponse()->getStatusCode(), $errorBody));
        } catch (Throwable $e) {
            throw new RuntimeException('Fallo general en la infraestructura del LLM: ' . $e->getMessage(), 0, $e);
        }
        */
    }

    /**
     * Función de simulación para desarrollo local.
     * Valida que el motor RAG recupera el contexto de PostgreSQL de forma matemática.
     */
    private function generateSimulatedResponse(string $prompt, array $context): string
    {
        $joinedContext = implode("\n\n", $context);

        $output = "=== RESPUESTA GENERADA POR MOTOR RAG (MODO SIMULACIÓN DESARROLLO) ===\n\n";
        $output .= "🔹 CONSULTA CLÍNICA RECIBIDA:\n\"" . $prompt . "\"\n\n";
        $output .= "🗂️ CONTEXTO RE REAL EXTRAÍDO DE POSTGRESQL (PGVECTOR):\n";
        
        if (empty($context)) {
            $output .= "⚠️ [ALERTA] No se han recuperado fragmentos de la base de datos vectorial.\n\n";
        } else {
            $output .= sprintf(" Se han recuperado %d fragmentos médicos relevantes de la tabla document_chunks.\n", count($context));
            $output .= "Muestra del contenido indexado en base de datos:\n";
            $output .= "--------------------------------------------------------------------------------\n";
            $output .= substr($joinedContext, 0, 450) . "...\n";
            $output .= "--------------------------------------------------------------------------------\n\n";
        }
        
        $output .= "📋 PROPUESTA DIETÉTICA BASADA EN LA EVIDENCIA RECOLECTADA:\n";
        $output .= "1. Restricción absoluta de lácteos tradicionales por déficit de enzima lactasa en mucosa intestinal.\n";
        $output .= "2. Cobertura de superávit calórico para hipertrofia mediante la Opción A del consenso clínico: Porridge de avena (60-80g) cocido con bebida vegetal (almendras/soja), enriquecido con un cacito (30g) de proteína vegetal aislado, crema de cacahuete y un plátano.\n";
        $output .= "3. Alternativa sólida: Revuelto de 2 huevos enteros y claras pasteurizadas sobre pan de masa madre y aguacate.";

        return $output;
    }
}