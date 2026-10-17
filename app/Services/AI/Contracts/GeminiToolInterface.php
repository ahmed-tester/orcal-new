<?php

namespace App\Services\AI\Contracts;

interface GeminiToolInterface
{
    /**
     * اسم الدالة الذي يراه Gemini
     */
    public function getName(): string;

    /**
     * الوصف والمواصفات التي يراها Gemini (الصندوق الخارجي فقط)
     */
    public function getDeclaration(): array;

    /**
     * التنفيذ البرمجي الحقيقي في السيرفر (مخفي تماماً عن Gemini)
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array;
}
