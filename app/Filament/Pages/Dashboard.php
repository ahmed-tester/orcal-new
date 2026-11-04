<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.filters.date_filter'))
                    ->description(__('dashboard.filters.date_filter_desc'))
                    ->icon('heroicon-o-funnel')
                    ->columnSpanFull()
                    ->collapsible()
                    ->columns([
                        'default' => 1,
                        'sm' => 3,
                    ])
                    ->schema([
                        Select::make('quick_range')
                            ->label(__('dashboard.filters.quick_range'))
                            ->placeholder(__('dashboard.filters.choose_range'))
                            ->options([
                                'today' => __('dashboard.filters.today'),
                                'this_week' => __('dashboard.filters.this_week'),
                                'this_month' => __('dashboard.filters.this_month'),
                                'last_month' => __('dashboard.filters.last_month'),
                                'this_quarter' => __('dashboard.filters.this_quarter'),
                                'this_year' => __('dashboard.filters.this_year'),
                                'clear' => __('dashboard.filters.all_time'),
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                match ($state) {
                                    'today' => [
                                        $set('startDate', now()->toDateString()),
                                        $set('endDate', now()->toDateString()),
                                    ],
                                    'this_week' => [
                                        $set('startDate', now()->startOfWeek()->toDateString()),
                                        $set('endDate', now()->endOfWeek()->toDateString()),
                                    ],
                                    'this_month' => [
                                        $set('startDate', now()->startOfMonth()->toDateString()),
                                        $set('endDate', now()->endOfMonth()->toDateString()),
                                    ],
                                    'last_month' => [
                                        $set('startDate', now()->subMonth()->startOfMonth()->toDateString()),
                                        $set('endDate', now()->subMonth()->endOfMonth()->toDateString()),
                                    ],
                                    'this_quarter' => [
                                        $set('startDate', now()->startOfQuarter()->toDateString()),
                                        $set('endDate', now()->endOfQuarter()->toDateString()),
                                    ],
                                    'this_year' => [
                                        $set('startDate', now()->startOfYear()->toDateString()),
                                        $set('endDate', now()->endOfYear()->toDateString()),
                                    ],
                                    'clear' => [
                                        $set('startDate', null),
                                        $set('endDate', null),
                                        $set('quick_range', null),
                                    ],
                                    default => null,
                                };
                            }),

                        DatePicker::make('startDate')
                            ->label(__('dashboard.filters.start_date'))
                            ->prefixIcon('heroicon-m-calendar')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->maxDate(fn ($get) => $get('endDate') ?: now())
                            ->afterStateUpdated(fn (callable $set) => $set('quick_range', null)),

                        DatePicker::make('endDate')
                            ->label(__('dashboard.filters.end_date'))
                            ->prefixIcon('heroicon-m-calendar')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->minDate(fn ($get) => $get('startDate'))
                            ->afterStateUpdated(fn (callable $set) => $set('quick_range', null)),
                    ]),
            ]);
    }
}
