<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface PdfExtractorInterface
{
    public function extractText(string $filePath): string;
}