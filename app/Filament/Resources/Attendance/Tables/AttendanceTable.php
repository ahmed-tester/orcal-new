<?php

namespace App\Filament\Resources\Attendance\Tables;

use App\Enums\AttendanceStatus;
use App\Models\Employee;
use App\Services\CompanyFormService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.employee_number')
                    ->label(__('attendance.fields.record_number'))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.user.name')
                    ->label(__('attendance.fields.employee'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('employee.branch.name')
                    ->label(__('leaves.fields.branch'))
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('date')
                    ->label(__('attendance.fields.date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('check_in')
                    ->label(__('attendance.fields.check_in'))
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('check_out')
                    ->label(__('attendance.fields.check_out'))
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('late_hours')
                    ->label(__('attendance.fields.late_hours'))
                    ->formatStateUsing(fn ($state) => (int) round((float) $state).' '.__('attendance.units.minute'))
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('overtime_hours')
                    ->label(__('attendance.fields.overtime_hours'))
                    ->formatStateUsing(fn ($state) => (int) round((float) $state).' '.__('attendance.units.minute'))
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('check_in_source')
                    ->label(__('attendance.fields.source'))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('device.name')
                    ->label(__('attendance.fields.device'))
                    ->icon('heroicon-m-finger-print')
                    ->placeholder(__('attendance.placeholders.manual_no_device'))
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('attendance.fields.status'))
                    ->badge(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('attendance.fields.status'))
                    ->options(AttendanceStatus::class),

                SelectFilter::make('employee_id')
                    ->label(__('attendance.fields.employee'))
                    ->relationship('employee', 'id')
                    ->getSearchResultsUsing(fn (?string $search) => CompanyFormService::employeeSearchResults($search))
                    ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->user?->name ?? "موظف #{$record->id}")
                    ->searchable()
                    ->preload(),

                Filter::make('date')
                    ->label(__('attendance.fields.date'))
                    ->form([
                        DatePicker::make('from')->label(__('leaves.fields.start_date')),
                        DatePicker::make('until')->label(__('leaves.fields.end_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('date', '<=', $date));
                    }),
            ])
            ->recordActions([
                EditAction::make()->label(__('admin.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.delete')),
                ]),
            ]);
    }
}
