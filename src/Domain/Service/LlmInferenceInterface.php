<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface LlmInferenceInterface
{
    /**
     * Genera una respuesta determinista a partir de un prompt y un contexto recuperado (RAG).
     * 
     * @param string $prompt La consulta clínica inicial.
     * @param array<int, string> $context Colección de textos recuperados de la base de datos vectorial.
     * @return string La respuesta o dieta generada por el LLM.
     */
    public function generateResponse(string $prompt, array $context): string;
}