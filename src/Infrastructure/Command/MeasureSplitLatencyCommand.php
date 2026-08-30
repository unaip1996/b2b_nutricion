<?php

namespace App\Infrastructure\Command;

use App\Domain\Service\EmbeddingGeneratorInterface;
use App\Domain\Service\LlmInferenceInterface;
use App\Infrastructure\Repository\DocumentChunkRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:measure-split-latency', description: 'Mide la latencia desglosada: Recuperación (pgvector) vs Inferencia (OpenAI)')]
class MeasureSplitLatencyCommand extends Command
{
    public function __construct(
        private EmbeddingGeneratorInterface $embeddingGenerator,
        private DocumentChunkRepository $documentChunkRepository,
        private LlmInferenceInterface $llmInference
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // NOTA: Lo bajamos a 5 iteraciones para evitar que la API de OpenAI 
        // te corte por "Rate Limit" (límite de peticiones por minuto) o sature tu terminal.
        $iterations = 5; 
        
        $totalRetrievalTime = 0;
        $totalInferenceTime = 0;
        $query = "Generar dieta estándar equilibrada para mantenimiento";

        $output->writeln("Iniciando validación OE-3 (Desglose de Latencias) - $iterations iteraciones");
        $output->writeln("⚠️ ADVERTENCIA: Esta prueba tardará unos 3 minutos en completarse.");
        $output->writeln("------------------------------------------------------------------");

        for ($i = 1; $i <= $iterations; $i++) {
            $output->write("Ejecutando Iteración $i... ");

            // ==========================================
            // FASE 1: RECUPERACIÓN (Tu Arquitectura)
            // ==========================================
            $startRetrieval = microtime(true);
            
            $queryVectorArray = $this->embeddingGenerator->generateEmbedding($query);
            $queryVectorString = json_encode($queryVectorArray); 
            $chunkEntities = $this->documentChunkRepository->findSimilarChunkEntities($queryVectorString, 4);
            
            $endRetrieval = microtime(true);
            $retrievalMs = round(($endRetrieval - $startRetrieval) * 1000, 2);
            $totalRetrievalTime += $retrievalMs;

            // Preparar el contexto (rápido, no penaliza)
            $contextTexts = [];
            foreach ($chunkEntities as $entity) {
                $contextTexts[] = $entity->getContent();
            }
            $contextString = implode("\n\n---\n\n", $contextTexts);

            // Mockeamos las variables del paciente para asegurar que el LLM procese 
            // la misma cantidad de tokens que en producción.
            $systemPrompt = <<<PROMPT
Eres un asistente clínico experto en nutrición. Utiliza EXCLUSIVAMENTE el siguiente contexto médico indexado para fundamentar tu propuesta.
Genera el JSON estructurado para EXACTAMENTE 7 días distintos.
CONTEXTO MÉDICO: $contextString
PERFIL DEL PACIENTE: Alergias: Ninguna. Patologías: Ninguna. Objetivo: 2000 kcal.
PROMPT;

            // ==========================================
            // FASE 2: INFERENCIA (OpenAI)
            // ==========================================
            $startInference = microtime(true);
            
            // Llamamos a OpenAI
            $this->llmInference->generateText($systemPrompt, $query);
            
            $endInference = microtime(true);
            $inferenceMs = round(($endInference - $startInference) * 1000, 2);
            $totalInferenceTime += $inferenceMs;

            // Imprimir resultado parcial de la iteración
            $output->writeln("[Recuperación: {$retrievalMs} ms | OpenAI: {$inferenceMs} ms]");
        }

        // Cálculos finales
        $avgRetrieval = round($totalRetrievalTime / $iterations, 2);
        $avgInference = round($totalInferenceTime / $iterations, 2);
        
        $output->writeln("=================================");
        $output->writeln("<info>Latencia Media RECUPERACIÓN (Pgvector + Embeddings): {$avgRetrieval} ms</info>");
        $output->writeln("<comment>Latencia Media INFERENCIA (OpenAI): {$avgInference} ms</comment>");
        $output->writeln("Latencia Media TOTAL: " . ($avgRetrieval + $avgInference) . " ms");
        
        if ($avgRetrieval < 1500) {
            $output->writeln("\n<info>✓ CONCLUSIÓN: El sistema RAG propio cumple el OE-3 (<1500ms).</info>");
        }

        return Command::SUCCESS;
    }
}