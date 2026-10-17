<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Collection;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected string $view = 'filament.resources.companies.pages.edit-company';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return array_map(fn ($value) => $value === '' ? null : $value, $data);
    }

    /**
     * Calculate human-readable duration between employment and termination / now in Arabic.
     */
    public static function calculateDuration(?string $employmentDate, ?string $terminationDate = null): string
    {
        if (!$employmentDate) {
            return '—';
        }

        try {
            $start = \Carbon\Carbon::parse($employmentDate);
            $end = $terminationDate ? \Carbon\Carbon::parse($terminationDate) : now();

            if ($start->gt($end)) {
                return '0 يوم';
            }

            $diff = $start->diff($end);
            $years = $diff->y;
            $months = $diff->m;
            $days = $diff->d;

            $parts = [];
            if ($years > 0) {
                if ($years == 1) {
                    $parts[] = 'سنة';
                } elseif ($years == 2) {
                    $parts[] = 'سنتان';
                } elseif ($years >= 3 && $years <= 10) {
                    $parts[] = "{$years} سنوات";
                } else {
                    $parts[] = "{$years} سنة";
                }
            }

            if ($months > 0) {
                if ($months == 1) {
                    $parts[] = 'شهر';
                } elseif ($months == 2) {
                    $parts[] = 'شهران';
                } elseif ($months >= 3 && $months <= 10) {
                    $parts[] = "{$months} أشهر";
                } else {
                    $parts[] = "{$months} شهر";
                }
            }

            if (empty($parts)) {
                return $days > 0 ? "{$days} يوم" : 'أقل من شهر';
            }

            return implode(' و ', $parts);
        } catch (\Throwable $e) {
            return '—';
        }
    }

    /**
     * Get owners belonging to the current company only.
     */
    public function getCompanyOwnersProperty(): Collection
    {
        return $this->record->owners()->with('user')->orderBy('id', 'desc')->get();
    }
}

