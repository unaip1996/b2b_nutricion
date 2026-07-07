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
            throw new \RuntimeException(sprintf('Error crítico: No se encontró el archivo PDF en "%s"', $filePath));
        }

        try {
            // INTENTO 1: Extracción nativa (rápida, para PDFs de texto digital)
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            // Limpieza básica conservando los saltos de línea vitales para el LLM
            $text = preg_replace('/[^\S\n]+/', ' ', $text);
            $text = trim($text);

            // Si el texto es ridículamente corto, es 100% seguro un PDF escaneado (imagen)
            if (strlen($text) < 50) {
                return $this->extractWithOcr($filePath);
            }

            return $text;
        } catch (\Exception $e) {
            // Si la librería Smalot falla (ej. PDF corrupto o encriptado), forzamos el OCR
            return $this->extractWithOcr($filePath);
        }
    }

    /**
     * Motor Fallback: Convierte el PDF a imágenes y aplica Inteligencia Artificial OCR
     */
    private function extractWithOcr(string $filePath): string
    {
        // 1. Creamos un directorio temporal seguro
        $tempDir = sys_get_temp_dir() . '/' . uniqid('ocr_', true);
        if (!mkdir($tempDir) && !is_dir($tempDir)) {
            throw new \RuntimeException('No se pudo crear el directorio temporal para el OCR.');
        }

        // 2. Usamos pdftoppm (poppler) para extraer páginas como imágenes JPG (150 DPI)
        $escapePath = escapeshellarg($filePath);
        $escapeDir = escapeshellarg($tempDir . '/page');
        exec("pdftoppm -jpeg -r 150 $escapePath $escapeDir", $output, $returnVar);

        if ($returnVar !== 0) {
            $this->cleanupTempDir($tempDir);
            throw new \RuntimeException("Fallo crítico al convertir el PDF a imágenes para OCR.");
        }

        $fullText = '';
        $images = glob($tempDir . '/page-*.jpg');
        sort($images); // Asegurarnos de que el texto va en orden de página

        // 3. Pasamos Tesseract OCR (en español) por cada imagen
        foreach ($images as $image) {
            $escapeImg = escapeshellarg($image);
            exec("tesseract $escapeImg stdout -l spa 2>/dev/null", $ocrOutput, $ocrReturnVar);
            
            if ($ocrReturnVar === 0) {
                $fullText .= implode("\n", $ocrOutput) . "\n\n";
            }
            
            // Borramos la imagen ya procesada para liberar RAM/Disco
            unlink($image);
        }

        $this->cleanupTempDir($tempDir);

        $fullText = preg_replace('/[^\S\n]+/', ' ', $fullText);
        
        if (strlen(trim($fullText)) < 10) {
            throw new \RuntimeException("El documento está completamente en blanco o es ilegible incluso para el motor OCR.");
        }

        return trim($fullText);
    }

    private function cleanupTempDir(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tempDir);
        }
    }
}