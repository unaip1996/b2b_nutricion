<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Entity\DocumentChunk;
use App\Infrastructure\Repository\DocumentChunkRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocumentChunkRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(DocumentChunkRepository::class);
    }

    public function testRepositoryCustomMethods(): void
    {
        // 1. Preparamos el escenario
        $doc = new ClinicalDocument();
        $doc->setFileName('guia_repo.pdf');
        $this->entityManager->persist($doc);

        $chunk = new DocumentChunk();
        $chunk->setContent('Contenido de prueba vectorial');
        $chunk->setEmbedding(json_encode([0.1, 0.2, 0.3]));
        $chunk->setMetadata(['file_name' => 'guia_repo.pdf', 'ingested_at' => '2026-07-18']);
        $chunk->setClinicalDocument($doc);
        
        $this->entityManager->persist($chunk);
        $this->entityManager->flush();

        $vector = [0.1, 0.2, 0.3];
        $vectorStr = json_encode($vector);

        // 2. Disparamos todas tus queries Nativas y de Postgres Vector
        $this->assertNotEmpty($this->repository->findSimilar($vector, 1));
        $this->assertNotEmpty($this->repository->findSimilarText($vector, 1));
        $this->assertNotEmpty($this->repository->findGroupedDocuments());
        $this->assertNotEmpty($this->repository->findSimilarChunkEntities($vectorStr, 1));
        
        // 3. Probamos el borrado lógico
        $this->repository->deleteByFileName('guia_repo.pdf');
        
        // Limpiamos la caché de Doctrine para obligarle a leer el nuevo estado de la Base de Datos
        $this->entityManager->clear();
        
        $deletedChunk = $this->entityManager->find(DocumentChunk::class, $chunk->getId());
        $this->assertNotNull($deletedChunk->getDeletedAt());
    }
}