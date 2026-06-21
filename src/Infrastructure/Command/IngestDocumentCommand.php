<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Application\UseCase\IngestClinicalDocumentUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:rag:ingest',
    description: 'Ingesta un documento PDF y genera sus embeddings'
)]
class IngestDocumentCommand extends Command
{
    public function __construct(
        private readonly IngestClinicalDocumentUseCase $useCase,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('filepath', InputArgument::REQUIRED, 'Ruta del documento PDF a ingestar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filepath = (string) $input->getArgument('filepath');

        $io->title('Iniciando ingesta de documento clínico (RAG)');
        $io->text(sprintf('Archivo a procesar: %s', $filepath));

        try {
            $this->useCase->execute($filepath);
            
            $io->success('El documento ha sido ingestado y sus embeddings generados y guardados correctamente.');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error(sprintf('Error durante la ingesta: %s', $e->getMessage()));
            
            return Command::FAILURE;
        }
    }
}
