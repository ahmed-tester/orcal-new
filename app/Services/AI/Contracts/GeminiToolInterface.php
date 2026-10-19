<?php

namespace App\Services\AI\Contracts;

interface GeminiToolInterface
{
    /**
     * The tool function name exposed to Gemini.
     */
    public function getName(): string;

    /**
     * Function declaration schema payload for Gemini.
     */
    public function getDeclaration(): array;

    /**
     * Execute the tool locally with given arguments.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array;
}
