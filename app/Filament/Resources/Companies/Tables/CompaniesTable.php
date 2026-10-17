<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label(__('organization.companies.fields.logo'))
                    ->circular()
                    ->state(fn ($record) => $record->logo ? asset('storage/' . $record->logo) : null)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode(mb_substr($record->trade_name ?? 'C', 0, 2)) . '&background=1a4478&color=ffffff'),

                TextColumn::make('company_number')
                    ->label(__('organization.companies.fields.company_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('trade_name')
                    ->label(__('organization.companies.fields.trade_name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => ($record->trade_name_en ? $record->trade_name_en . ' | ' : '') . ($record->phone ? '📱 ' . PhoneService::format($record->phone) : '') . ($record->email ? ' | ✉️ ' . $record->email : '')),

                TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label(__('organization.companies.fields.branches_count'))
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('commercial_registration_number')
                    ->label(__('organization.companies.fields.cr_number'))
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('commercial_registration_expiry')
                    ->label(__('organization.companies.fields.cr_expiry_date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->badge()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($state), false);
                        return match (true) {
                            $days < 0 || $days <= 30 => 'danger',
                            $days <= 90              => 'warning',
                            default                  => 'success',
                        };
                    })
                    ->icon(function ($state) {
                        if (!$state) return null;
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($state), false);
                        return match (true) {
                            $days < 0   => 'heroicon-m-x-circle',
                            $days <= 30 => 'heroicon-m-exclamation-triangle',
                            $days <= 90 => 'heroicon-m-clock',
                            default     => 'heroicon-m-check-circle',
                        };
                    })
                    ->description(function ($state) {
                        if (!$state) return null;
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($state), false);
                        return match (true) {
                            $days < 0  => 'منتهي منذ ' . abs($days) . ' يوم',
                            $days == 0 => 'ينتهي اليوم',
                            default    => 'متبقي ' . $days . ' يوم',
                        };
                    }),

                TextColumn::make('status')
                    ->label(__('organization.companies.fields.status'))
                    ->badge(),
            ])
            ->filters([


                SelectFilter::make('status')
                    ->label(__('organization.companies.fields.status'))
                    ->options(\App\Enums\GeneralStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}
