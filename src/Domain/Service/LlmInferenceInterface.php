<?php
declare(strict_types=1);
namespace App\Domain\Service;

interface LlmInferenceInterface {
    public function generateText(string $systemPrompt, string $userPrompt): string;
}