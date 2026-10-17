<?php

namespace App\Services\Reports\Contracts;

interface ReportInterface
{
    public function getKey(): string;

    public function getTitle(): string;

    public function getLabel(): string;

    public function getIcon(): string;

    public function getExportFilename(): string;

    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array;

    public function getQuery(?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search);

    public function getSortableColumns(): array;

    public function getColumns(): array;

    public function formatRow($item, bool $isCsv = false): array;
}
