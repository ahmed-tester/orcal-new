<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Concerns\RedirectsToIndex;

class EditCompany extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = CompanyResource::class;

    protected string $view = 'filament.resources.companies.pages.edit-company';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('organization.companies.actions.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('companies.print', $this->record))
                ->openUrlInNewTab(),
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
            return 'â€”';
        }

        try {
            $start = \Carbon\Carbon::parse($employmentDate);
            $end = $terminationDate ? \Carbon\Carbon::parse($terminationDate) : now();

            if ($start->gt($end)) {
                return '0 ظٹظˆظ…';
            }

            $diff = $start->diff($end);
            $years = $diff->y;
            $months = $diff->m;
            $days = $diff->d;

            $parts = [];
            if ($years > 0) {
                if ($years == 1) {
                    $parts[] = 'ط³ظ†ط©';
                } elseif ($years == 2) {
                    $parts[] = 'ط³ظ†طھط§ظ†';
                } elseif ($years >= 3 && $years <= 10) {
                    $parts[] = "{$years} ط³ظ†ظˆط§طھ";
                } else {
                    $parts[] = "{$years} ط³ظ†ط©";
                }
            }

            if ($months > 0) {
                if ($months == 1) {
                    $parts[] = 'ط´ظ‡ط±';
                } elseif ($months == 2) {
                    $parts[] = 'ط´ظ‡ط±ط§ظ†';
                } elseif ($months >= 3 && $months <= 10) {
                    $parts[] = "{$months} ط£ط´ظ‡ط±";
                } else {
                    $parts[] = "{$months} ط´ظ‡ط±";
                }
            }

            if (empty($parts)) {
                return $days > 0 ? "{$days} ظٹظˆظ…" : 'ط£ظ‚ظ„ ظ…ظ† ط´ظ‡ط±';
            }

            return implode(' ظˆ ', $parts);
        } catch (\Throwable $e) {
            return 'â€”';
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

