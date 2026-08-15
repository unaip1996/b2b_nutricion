<?php
declare(strict_types=1);

namespace App\Tests\Stub;

use App\Domain\Service\PdfExtractorInterface;

class MockPdfExtractor implements PdfExtractorInterface
{
    // Cambiamos UploadedFile por string para respetar tu interfaz original
    public function extractText(string $filePath): string
    {
        return "Texto médico de prueba extraído correctamente.";
    }
}