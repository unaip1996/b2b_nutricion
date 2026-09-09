<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Infrastructure\Entity\ClinicalDocument;
use App\Infrastructure\Entity\DocumentChunk;
use PHPUnit\Framework\TestCase;

class ClinicalDocumentTest extends TestCase
{
    public function testClinicalDocumentRemaining(): void
    {
        $doc = new ClinicalDocument();
        $this->assertNull($doc->getId());
        
        $date = new \DateTimeImmutable();
        $doc->setDeletedAt($date);
        $this->assertSame($date, $doc->getDeletedAt());
        
        $chunk = new DocumentChunk();
        $doc->addChunk($chunk);
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $doc->getChunks());
        $this->assertTrue($doc->getChunks()->contains($chunk));
    }

    public function testClinicalDocumentRemainingMethods(): void
    {
        $doc = new ClinicalDocument();
        $this->assertNotNull($doc->getIngestedAt());
        
        $chunk = new DocumentChunk();
        $doc->addChunk($chunk);
        $doc->addChunk($chunk); 
        
        $this->assertTrue($doc->getChunks()->contains($chunk));

        $date = new \DateTimeImmutable();
        $doc->setDeletedAt($date);
        $this->assertSame($date, $doc->getDeletedAt());
    }
}
