<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use Filament\Resources\Pages\EditRecord;

class EditViolationTransaction extends EditRecord
{
    protected static string $resource = ViolationTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action omitted to protect transaction audit trail
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $linkType = $data['link_type'] ?? 'single';
        $data['metadata'] = array_merge($data['metadata'] ?? [], [
            'link_type' => $linkType,
        ]);

        if ($linkType === 'unlinked') {
            $data['violation_id'] = null;
            unset($data['metadata']['selected_violation_ids']);
        } elseif ($linkType === 'multiple') {
            $data['violation_id'] = null;
        }

        return $data;
    }
}
