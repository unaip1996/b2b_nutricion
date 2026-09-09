<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Repository\ClinicalDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClinicalDocumentRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(ClinicalDocumentRepository::class);
    }

    public function testFindAllActiveAndSearch(): void
    {
        // 1. Arrange
        $doc1 = new ClinicalDocument();
        $doc1->setFileName('guia_obesidad.pdf');
        $this->entityManager->persist($doc1);

        $doc2 = new ClinicalDocument();
        $doc2->setFileName('guia_diabetes.pdf');
        $this->entityManager->persist($doc2);

        $docDeleted = new ClinicalDocument();
        $docDeleted->setFileName('borrado.pdf');
        $docDeleted->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->persist($docDeleted);

        $this->entityManager->flush();

        // 2. Test findAllActive
        $activeDocs = $this->repository->findAllActive();
        // Puede haber más en la BBDD de otros tests, pero verificamos que no contenga el borrado
        $containsDeleted = false;
        foreach ($activeDocs as $doc) {
            if ($doc->getFileName() === 'borrado.pdf') {
                $containsDeleted = true;
            }
        }
        $this->assertFalse($containsDeleted);

        // 3. Test searchAndPaginateActive
        $result = $this->repository->searchAndPaginateActive(['title' => 'obesidad'], 1, 10);
        $this->assertIsArray($result['items']);
        $this->assertIsInt($result['total']);
        $this->assertGreaterThan(0, $result['total']);
        
        $foundTitle = false;
        foreach ($result['items'] as $doc) {
            if (str_contains($doc->getFileName(), 'obesidad')) {
                $foundTitle = true;
            }
        }
        $this->assertTrue($foundTitle);
    }
}
