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
        
        // 1. Inyección estricta de parámetros para GenerateClinicalDietUseCase::execute
        // Asumimos el paciente "1" (asegúrate de que este ID existe en tu BD tras el volcado)
        $patientId = "019edaa3-dcd9-7f1e-8507-cb5397d28689"; 
        $query = "Generar dieta estándar equilibrada para mantenimiento";
        $kcal = 2000;
        $startDate = new \DateTimeImmutable('tomorrow');
        $endDate = new \DateTimeImmutable('tomorrow + 6 days'); // 7 días totales

        $output->writeln("Iniciando validación OE-3 (RAG < 1500ms) - $iterations iteraciones");
        $output->writeln("Generando dieta de 7 días, $kcal kcal...");
        $output->writeln("------------------------------------------------------------------");

        for ($i = 1; $i <= $iterations; $i++) {
            $start = microtime(true);

            // 2. Ejecución del motor RAG completo
            $this->generateDietUseCase->execute(
                $patientId, 
                $query, 
                $kcal, 
                $startDate, 
                $endDate
            );

            $end = microtime(true);
            $latencyMs = round(($end - $start) * 1000, 2);
            $totalTime += $latencyMs;

            $output->writeln("Iteración $i: {$latencyMs} ms");
        }

        // 3. Cálculo de métricas
        $average = round($totalTime / $iterations, 2);
        $output->writeln("=================================");
        $output->writeln("Latencia Media: {$average} ms");
        
        if ($average < 1500) {
            $output->writeln("<info>✓ ÉXITO: El motor RAG cumple el OE-3 holgadamente.</info>");
        } else {
            $output->writeln("<error>✗ ALERTA: La latencia supera los 1500 ms del OE-3.</error>");
        }

        return Command::SUCCESS;
    }
}