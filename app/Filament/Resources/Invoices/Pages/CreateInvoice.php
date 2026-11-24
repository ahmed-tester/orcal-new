<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceFinancialService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Filament\Concerns\RedirectsToIndex;

class CreateInvoice extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = InvoiceResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterCreate(): void
    {
        try {
            DB::transaction(function (): void {
                /** @var Invoice $record */
                $record = $this->getRecord();

                InvoiceFinancialService::recalculateTotals($record);
                InvoiceFinancialService::activate($record->refresh(), auth()->user());
                InvoiceFinancialService::recordCreationReceipt($record->refresh(), $this->data ?? [], auth()->user());
            });
        } catch (ValidationException $e) {
            Log::warning('Invoice creation receipt validation failed', [
                'user_id' => auth()->id(),
                'company_id' => $this->data['company_id'] ?? null,
                'branch_id' => $this->data['branch_id'] ?? null,
                'errors' => $e->errors(),
            ]);

            $firstError = collect($e->errors())->flatten()->first() ?? __('financial.errors.collector_required');

            Notification::make()
                ->danger()
                ->title($firstError)
                ->send();

            throw $e;
        } catch (Throwable $e) {
            Log::error('Invoice creation failed in afterCreate', [
                'user_id' => auth()->id(),
                'company_id' => $this->data['company_id'] ?? null,
                'branch_id' => $this->data['branch_id'] ?? null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();

            throw $e;
        }
    }
}
