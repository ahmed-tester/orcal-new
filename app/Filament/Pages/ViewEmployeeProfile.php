<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Services\EmployeePerformanceAccessService;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

abstract class ViewEmployeeProfile extends ViewRecord
{
    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;
        $user = auth()->user();

        return $record instanceof Employee
            && $user !== null
            && static::getResource()::canView($record)
            && EmployeePerformanceAccessService::canViewEmployeeRecord($user, $record);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canAccess(['record' => $this->getRecord()]), 403);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')->label(__('employees.fields.name')),
            TextEntry::make('employee_number')->label(__('employees.fields.employee_number')),
            TextEntry::make('company.trade_name')->label(__('employees.fields.company')),
            TextEntry::make('branch.name')->label(__('employees.fields.branch')),
            TextEntry::make('department.name')->label(__('employees.fields.department')),
        ])->columns(2);
    }

    protected function getHeaderActions(): array
    {
        if (! static::getResource()::hasPage('performance')) {
            return [];
        }

        $record = $this->getRecord();
        $performancePage = static::getResource()::getPages()['performance']->getPage();

        return [
            Action::make('performance')
                ->label(__('tasks.performance.action'))
                ->icon('heroicon-o-chart-bar')
                ->visible(fn () => $performancePage::canAccess(['record' => $record]))
                ->url(fn () => static::getResource()::getUrl('performance', ['record' => $record])),
        ];
    }
}
