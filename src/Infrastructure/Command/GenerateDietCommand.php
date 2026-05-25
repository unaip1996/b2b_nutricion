<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Application\UseCase\GenerateClinicalDietUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:rag:generate',
    description: 'Prueba el motor RAG consultando una dieta'
)]
class GenerateDietCommand extends Command
{
    public function __construct(
        private readonly GenerateClinicalDietUseCase $useCase,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('query', InputArgument::REQUIRED, 'La consulta clínica (ej: "Dieta para paciente con hipertensión")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = (string) $input->getArgument('query');

        $io->title('Generador de Dietas Clínicas (RAG)');
        $io->info('Pensando... Consultando base de conocimientos biomédica');

        try {
            $response = $this->useCase->execute($query);

            $io->success('Resultado generado con éxito:');
            $io->writeln($response);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error('Fallo al generar la dieta: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}