<?php

namespace App\Filament\Resources\Owners\Tables;

use App\Enums\GeneralStatus;
use App\Models\Owner;
use App\Services\OwnerFormService;
use App\Services\OwnerSalaryService;
use App\Services\PersonCapacityService;
use App\Services\PhoneService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with([
                'user.employee',
                'company',
            ]))
            ->columns([
                ImageColumn::make('image')
                    ->label(__('owners.fields.image'))
                    ->circular()
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        if (! empty($record->image) && Storage::disk('public')->exists($record->image)) {
                            return $record->image;
                        }

                        return null;
                    })
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode(mb_substr($record->user?->name ?? 'م', 0, 2)).'&background=1a4478&color=ffffff'),

                TextColumn::make('user.name')
                    ->label(__('owners.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $parts = [];
                        if (! empty($record->name_en)) {
                            $parts[] = '🔤 '.$record->name_en;
                        }
                        if ($record->user?->phone) {
                            $parts[] = '📱 '.PhoneService::format($record->user->phone);
                        }
                        if ($record->user?->email) {
                            $parts[] = '✉️ '.$record->user->email;
                        }

                        return implode(' | ', $parts);
                    }),

                PersonCapacityService::makeTableColumn('owner'),

                TextColumn::make('company.trade_name')
                    ->label(__('owners.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('national_id')
                    ->label(__('owners.fields.national_id'))
                    ->searchable(),

                TextColumn::make('ownership_percentage')
                    ->label(__('owners.fields.ownership_percentage'))
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label(__('owners.fields.status'))
                    ->badge(),

                TextColumn::make('monthly_deduction_percentage')
                    ->label(__('owners.fields.monthly_deduction_percentage'))
                    ->suffix('%')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('owner_advances')
                    ->label(__('owners.fields.owner_advances'))
                    ->money('SAR')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('debts')
                    ->label(__('owners.fields.debts'))
                    ->money('SAR')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('other_amount')
                    ->label(__('owners.fields.other_amount'))
                    ->money('SAR')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('basic_salary')
                    ->label(__('owners.fields.basic_salary'))
                    ->money('SAR')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('other_financials')
                    ->label(__('owners.fields.other_financials'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('owners.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('status')
                    ->label(__('owners.fields.status'))
                    ->options(GeneralStatus::class),

                PersonCapacityService::makeTableFilter('owner'),
            ])
            ->recordActions([
                EditAction::make()->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                self::bulkBasicSalaryAction(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }

    private static function bulkBasicSalaryAction(): Action
    {
        return Action::make('bulkBasicSalary')
            ->label(__('owners.salary_bulk.action'))
            ->icon('heroicon-o-banknotes')
            ->authorize(fn () => Gate::allows('update', new Owner))
            ->modalDescription(__('owners.salary_bulk.description'))
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel(__('owners.salary_bulk.save'))
            ->fillForm(fn (): array => ['owners' => OwnerSalaryService::rowsForReview()])
            ->schema([
                OwnerFormService::makeAmountInput('amount', 'owners.salary_bulk.amount')
                    ->saved(false)
                    ->belowContent(Action::make('applySalary')
                        ->label(__('owners.salary_bulk.apply'))
                        ->failureNotificationTitle(__('owners.salary_bulk.invalid_amount'))
                        ->action(function (Get $get, Set $set, Action $action): void {
                            $amount = $get('amount');
                            if (Validator::make(['amount' => $amount], [
                                'amount' => OwnerSalaryService::amountRules(),
                            ])->fails()) {
                                $action->failure();

                                return;
                            }

                            $rows = collect($get('owners'))->map(function (array $row) use ($amount): array {
                                $row['basic_salary'] = round((float) $amount, 2);

                                return $row;
                            });

                            $set('owners', $rows->all());
                        })),
                Repeater::make('owners')
                    ->label(__('owners.plural'))
                    ->table([
                        TableColumn::make(__('owners.fields.name')),
                        TableColumn::make(__('owners.fields.company')),
                        TableColumn::make(__('owners.fields.basic_salary')),
                    ])
                    ->schema([
                        Hidden::make('owner_id')->required()->rule('integer'),
                        TextInput::make('owner_name')->label(__('owners.fields.name'))->disabled(),
                        TextInput::make('company_name')->label(__('owners.fields.company'))->disabled(),
                        OwnerFormService::makeAmountInput('basic_salary', 'owners.fields.basic_salary')->required(),
                    ])
                    ->minItems(1)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ])
            ->action(function (array $data, Action $action): void {
                $count = OwnerSalaryService::save($data['owners']);
                $action->successNotificationTitle(__('owners.salary_bulk.saved', ['count' => $count]));
                $action->success();
            });
    }
}
