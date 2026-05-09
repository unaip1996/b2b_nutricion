<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Domain\Service\PdfExtractorInterface;
use Smalot\PdfParser\Parser;

class BasicPdfExtractor implements PdfExtractorInterface
{
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('Error crítico: No se encontró el archivo PDF en la ruta "%s"', $filePath));
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            
            $text = $pdf->getText();
            
            if (empty(trim($text))) {
                throw new \RuntimeException('El PDF fue leído pero no contiene texto extraíble (podría ser una imagen escaneada).');
            }

            return $text;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Fallo al parsear el PDF: ' . $e->getMessage());
        }
    }
}