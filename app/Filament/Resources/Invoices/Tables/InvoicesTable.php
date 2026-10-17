<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\PaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('invoices.fields.invoice_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-document-text')
                    ->url(fn (\App\Models\Invoice $record) => route('invoices.print', $record))
                    ->openUrlInNewTab()
                    ->tooltip('انقر لطباعة وحفظ الفاتورة 🖨️'),

                TextColumn::make('company.trade_name')
                    ->label(__('invoices.fields.company'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('invoices.fields.customer'))
                    ->placeholder(fn ($record) => $record->employee ? 'موظف: ' . ($record->employee->user->name ?? '#' . $record->employee->id) : '—')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('invoiceType.name')
                    ->label(__('invoices.fields.invoice_type'))
                    ->badge()
                    ->color('info')
                    ->placeholder('خدمات عامة'),

                TextColumn::make('issue_date')
                    ->label(__('invoices.fields.issue_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label(__('invoices.fields.total_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('paid_amount')
                    ->label(__('invoices.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('remaining_amount')
                    ->label(__('invoices.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('payment_status')
                    ->label(__('invoices.fields.payment_status'))
                    ->badge(),

                IconColumn::make('is_paid_by_applicant')
                    ->label('دفع طالب الخدمة')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('invoices.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('invoice_type_id')
                    ->label(__('invoices.fields.invoice_type'))
                    ->relationship('invoiceType', 'name'),

                SelectFilter::make('payment_status')
                    ->label(__('invoices.fields.payment_status'))
                    ->options(PaymentStatus::class),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('print')
                    ->label('طباعة الفاتورة')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\App\Models\Invoice $record) => route('invoices.print', $record))
                    ->openUrlInNewTab(),
                EditAction::make()->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}
