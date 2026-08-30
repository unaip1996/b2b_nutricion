<?php

namespace App\Infrastructure\Command;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:measure-rag-latency', description: 'Mide la latencia del motor RAG en 10 iteraciones (OE-3)')]
class MeasureRagLatencyCommand extends Command
{
    private GenerateClinicalDietUseCase $generateDietUseCase;

    public function __construct(GenerateClinicalDietUseCase $generateDietUseCase)
    {
        parent::__construct();
        $this->generateDietUseCase = $generateDietUseCase;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $iterations = 10;
        $totalTime = 0;
        
        // Ajusta los parámetros según lo que exija tu GenerateClinicalDietUseCase.php
        $patientId = 1; 
        $prompt = "Generar dieta estándar para mantenimiento de peso";

        $output->writeln("Iniciando medición de latencia RAG (Objetivo OE-3) - $iterations iteraciones...");
        $output->writeln("------------------------------------------------------------------");

        for ($i = 1; $i <= $iterations; $i++) {
            $start = microtime(true);

            // Ejecutamos el caso de uso principal del RAG
            $this->generateDietUseCase->execute($patientId, $prompt);

            $end = microtime(true);
            $latencyMs = round(($end - $start) * 1000, 2);
            $totalTime += $latencyMs;

            $output->writeln("Iteración $i: {$latencyMs} ms");
        }

        $average = round($totalTime / $iterations, 2);
        $output->writeln("=================================");
        $output->writeln("Latencia Media: {$average} ms");
        
        if ($average < 1500) {
            $output->writeln("<info>✓ ÉXITO: El motor RAG cumple el OE-3.</info>");
        } else {
            $output->writeln("<error>✗ PELIGRO: La latencia supera los 1500 ms.</error>");
        }

        return Command::SUCCESS;
    }
}