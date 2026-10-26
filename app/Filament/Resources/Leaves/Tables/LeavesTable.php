<?php

namespace App\Filament\Resources\Leaves\Tables;

use App\Enums\LeaveStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\CompanyFormService;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeavesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.employee_number')
                    ->label(__('leaves.fields.record_number'))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.user.name')
                    ->label(__('leaves.fields.employee'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('leaveType.name')
                    ->label(__('leaves.fields.leave_type'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('leaves.fields.start_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('leaves.fields.end_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('actual_return_date')
                    ->label(__('leaves.fields.actual_return_date'))
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('return_status_label')
                    ->label(__('leaves.fields.return_status'))
                    ->badge()
                    ->color(fn (Leave $record) => $record->return_status_color),

                TextColumn::make('days')
                    ->label(__('leaves.fields.days'))
                    ->suffix(' '.__('leaves.units.day'))
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('leaves.fields.status'))
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('leaves.fields.status'))
                    ->options(LeaveStatus::class),

                SelectFilter::make('leave_type_id')
                    ->label(__('leaves.fields.leave_type'))
                    ->options(fn () => LeaveType::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('employee_id')
                    ->label(__('leaves.fields.employee'))
                    ->relationship('employee', 'id')
                    ->getSearchResultsUsing(fn (?string $search) => CompanyFormService::employeeSearchResults($search))
                    ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->user?->name ?? (__('employees.single')." #{$record->id}"))
                    ->searchable()
                    ->preload(),

                Filter::make('start_date')
                    ->label(__('leaves.fields.start_date'))
                    ->form([
                        DatePicker::make('from')->label(__('leaves.fields.start_date')),
                        DatePicker::make('until')->label(__('leaves.fields.end_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('end_date', '<=', $date));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label(__('leaves.actions.approve'))
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (Leave $record) => $record->status !== LeaveStatus::APPROVED && auth()->user()?->can('change_leave_status', $record))
                        ->requiresConfirmation()
                        ->modalHeading(__('leaves.actions.approve_confirm_heading'))
                        ->modalDescription(__('leaves.actions.approve_confirm_description'))
                        ->action(function (Leave $record) {
                            $leaveType = LeaveType::find($record->leave_type_id);
                            if ($leaveType) {
                                $usedOther = (int) Leave::where('employee_id', $record->employee_id)
                                    ->where('leave_type_id', $record->leave_type_id)
                                    ->where('status', 'approved')
                                    ->where('id', '!=', $record->id)
                                    ->sum('days');
                                $remaining = max(0, (int) $leaveType->annual_balance - $usedOther);

                                if ($record->days > $remaining) {
                                    Notification::make()
                                        ->title(__('leaves.notifications.insufficient_balance', ['available' => $remaining, 'days' => $record->days]))
                                        ->danger()
                                        ->send();

                                    return;
                                }
                            }

                            $record->update([
                                'status' => LeaveStatus::APPROVED,
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                            ]);

                            Notification::make()
                                ->title(__('leaves.notifications.approved_success'))
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label(__('leaves.actions.reject'))
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (Leave $record) => $record->status !== LeaveStatus::REJECTED && auth()->user()?->can('change_leave_status', $record))
                        ->modalHeading(__('leaves.actions.reject_confirm_heading'))
                        ->form([
                            TextInput::make('rejection_reason')
                                ->label(__('leaves.fields.rejection_reason'))
                                ->placeholder(__('leaves.fields.rejection_reason_placeholder'))
                                ->required(),
                        ])
                        ->action(function (Leave $record, array $data) {
                            $record->update([
                                'status' => LeaveStatus::REJECTED,
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'rejection_reason' => $data['rejection_reason'] ?? null,
                            ]);

                            Notification::make()
                                ->title(__('leaves.notifications.rejected_success'))
                                ->danger()
                                ->send();
                        }),

                    Action::make('register_return')
                        ->label(__('leaves.actions.register_return'))
                        ->icon('heroicon-m-arrow-left-on-rectangle')
                        ->color('info')
                        ->modalHeading(__('leaves.actions.register_return'))
                        ->form([
                            DatePicker::make('actual_return_date')
                                ->label(__('leaves.actions.register_return_label'))
                                ->placeholder(__('leaves.fields.actual_return_date_placeholder'))
                                ->default(now())
                                ->native(false)
                                ->required(),
                        ])
                        ->action(function (Leave $record, array $data) {
                            $record->update([
                                'actual_return_date' => $data['actual_return_date'],
                            ]);

                            $diff = $record->end_date->diffInDays(Carbon::parse($data['actual_return_date'])->startOfDay(), false);
                            $unit = abs($diff) == 1 ? __('leaves.units.day') : __('leaves.units.days');

                            if ($diff < 0) {
                                $suffix = __('leaves.notifications.return_early', ['days' => abs($diff), 'unit' => $unit]);
                            } elseif ($diff === 0) {
                                $suffix = __('leaves.notifications.return_on_time');
                            } else {
                                $suffix = __('leaves.notifications.return_late', ['days' => $diff, 'unit' => $unit]);
                            }

                            Notification::make()
                                ->title(__('leaves.notifications.return_registered').' '.$suffix)
                                ->success()
                                ->send();
                        }),

                    Action::make('print')
                        ->label(__('leaves.actions.print'))
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn (Leave $record) => route('leaves.print', $record))
                        ->openUrlInNewTab(),

                    EditAction::make()->label(__('admin.edit')),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->headerActions([
                Action::make('print_report')
                    ->label(__('leaves.actions.print_report'))
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->modalHeading(__('leaves.actions.print_report_heading'))
                    ->modalDescription(__('leaves.actions.print_report_desc'))
                    ->form([
                        DatePicker::make('date_from')
                            ->label(__('leaves.actions.date_from'))
                            ->default(now()->startOfMonth()->toDateString())
                            ->native(false)
                            ->required(),

                        DatePicker::make('date_to')
                            ->label(__('leaves.actions.date_to'))
                            ->default(now()->endOfMonth()->toDateString())
                            ->native(false)
                            ->required(),

                        Select::make('company_id')
                            ->label(__('leaves.fields.company'))
                            ->options(fn () => Company::pluck('trade_name', 'id'))
                            ->placeholder(__('leaves.actions.all_companies'))
                            ->searchable(),

                        Select::make('leave_type_id')
                            ->label(__('leaves.fields.leave_type'))
                            ->options(fn () => LeaveBalanceService::getLeaveTypeOptions())
                            ->placeholder(__('leaves.actions.all_types'))
                            ->searchable(),

                        Select::make('status')
                            ->label(__('leaves.fields.status'))
                            ->options([
                                'all' => __('leaves.actions.all_types') ? __('admin.all') ?? 'الكل' : 'الكل',
                                'approved' => __('leaves.statuses.approved'),
                                'pending' => __('leaves.statuses.pending'),
                                'rejected' => __('leaves.statuses.rejected'),
                            ])
                            ->default('all'),
                    ])
                    ->action(function (array $data) {
                        $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');

                        return redirect()->to(route('leaves.report.print', $params));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.delete')),
                ]),
            ]);
    }
}
