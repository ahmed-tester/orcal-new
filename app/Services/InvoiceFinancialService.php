<?php

namespace App\Services;

use App\Enums\FinancialPaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\FinancialReceipt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceFinancialService
{
    public static function activate(Invoice $invoice, ?User $actor = null): Invoice
    {
        if ($invoice->isServiceMirror()) {
            throw ValidationException::withMessages([
                'invoice' => __('invoices.errors.service_mirror_locked'),
            ]);
        }

        $meta = $invoice->metadata ?? [];
        $meta[Invoice::FLOW_KEY] = Invoice::WORKFLOW_VERSION;
        $meta['financial_flow'] = Invoice::FLOW_MANAGED;
        $meta['managed_activated_by'] = $meta['managed_activated_by'] ?? $actor?->id ?? auth()->id();
        $meta['managed_activated_at'] = $meta['managed_activated_at'] ?? now()->toDateTimeString();

        $total = round(max(0.0, (float) $invoice->total_amount), 2);

        $invoice->metadata = $meta;
        $invoice->total_amount = $total;
        $invoice->paid_amount = 0.0;
        $invoice->remaining_amount = $total;
        $invoice->payment_status = $total > 0 ? PaymentStatus::UNPAID : PaymentStatus::PAID;
        $invoice->saveQuietly();

        return $invoice;
    }

    /**
     * Activate a new or empty legacy independent invoice for receipt/handover tracking.
     */
    public static function ensureManagedForReceipt(Invoice $invoice, ?User $actor = null): Invoice
    {
        if ($invoice->isServiceMirror()) {
            throw ValidationException::withMessages([
                'invoice' => __('invoices.errors.service_mirror_locked'),
            ]);
        }

        if ($invoice->isManaged()) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice, $actor): Invoice {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->isServiceMirror()) {
                throw ValidationException::withMessages([
                    'invoice' => __('invoices.errors.service_mirror_locked'),
                ]);
            }

            if ($locked->isManaged()) {
                return $locked;
            }

            $meta = $locked->metadata ?? [];
            $hasReceipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $locked->id)->exists();

            if ((float) $locked->paid_amount > 0
                || $hasReceipt
                || $locked->payment_status === PaymentStatus::PAID
                || $locked->payment_status === PaymentStatus::PARTIALLY_PAID
                || ! empty($meta['reversed_at'])
                || ! empty($meta['handover_id'])
                || ! empty($meta['treasury_transaction_id'])) {
                throw ValidationException::withMessages([
                    'invoice' => __('invoices.errors.legacy_invoice_not_eligible'),
                ]);
            }

            static::activate($locked, $actor);

            OperationsAuditService::recordFor(
                model: $locked,
                actionType: 'invoice_managed_activated',
                payload: [
                    'invoice_number' => $locked->invoice_number,
                    'total_amount' => (float) $locked->total_amount,
                ],
                userId: $actor?->id ?? auth()->id(),
                companyId: (int) $locked->company_id,
            );

            return $locked->refresh();
        });
    }

    /**
     * Recalculate invoice items and header totals server-side inside a transaction.
     */
    public static function recalculateTotals(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($locked->isServiceMirror() || $locked->hasFinancialImpact()) {
                return $locked;
            }

            if ($locked->items->isNotEmpty()) {
                $grandTotal = 0.0;

                foreach ($locked->items as $item) {
                    $qty = round(max(0.0, (float) $item->quantity), 2);
                    $unitPrice = round(max(0.0, (float) $item->unit_price), 2);
                    $taxRate = round(max(0.0, (float) $item->tax_rate), 2);
                    $subtotal = round($qty * $unitPrice, 2);
                    $taxAmount = round($subtotal * ($taxRate / 100), 2);
                    $itemTotal = round($subtotal + $taxAmount, 2);

                    $item->update([
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $itemTotal,
                    ]);

                    $grandTotal += $itemTotal;
                }

                $locked->total_amount = round($grandTotal, 2);
                $locked->saveQuietly();
            }

            return $locked->isManaged() ? static::sync($locked) : $locked->refresh();
        });
    }

    public static function sync(Invoice $invoice): Invoice
    {
        if (! $invoice->isManaged() || $invoice->isServiceMirror()) {
            return $invoice;
        }

        $summary = static::summary($invoice);
        $meta = $invoice->metadata ?? [];

        if ((float) $summary['cash_received'] > 0) {
            $meta['custody_status'] = (float) $summary['custody'] > 0 ? 'on_employee' : 'in_treasury';
        } elseif ((float) $summary['received'] > 0) {
            $meta['custody_status'] = 'not_applicable';
        } else {
            $meta['custody_status'] = 'none';
        }

        $meta['financial_synced_at'] = now()->toDateTimeString();

        $applied = (float) $summary['applied'];
        $remaining = (float) $summary['remaining'];
        $due = (float) $summary['due'];

        $invoice->metadata = $meta;
        $invoice->paid_amount = $applied;
        $invoice->remaining_amount = $remaining;
        $invoice->payment_status = match (true) {
            $remaining <= 0 && ($applied > 0 || $due <= 0) => PaymentStatus::PAID,
            $applied > 0 => PaymentStatus::PARTIALLY_PAID,
            default => PaymentStatus::UNPAID,
        };
        $invoice->saveQuietly();

        return $invoice->refresh();
    }

    /**
     * @return array<string, float|int>
     */
    public static function summary(Invoice $invoice): array
    {
        return FinancialSummaryService::forReceipts(
            FinancialReceipt::SOURCE_INVOICE,
            (int) $invoice->id,
            (float) $invoice->total_amount,
        );
    }

    /**
     * Create an independent managed invoice from validated data/items.
     * Ignores manual paid_amount; receipts are the only source of truth.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function createIndependent(array $data, array $items = [], ?User $actor = null): Invoice
    {
        return DB::transaction(function () use ($data, $items, $actor): Invoice {
            unset($data['paid_amount'], $data['remaining_amount'], $data['payment_status']);

            $data['created_by'] = $data['created_by'] ?? $actor?->id ?? auth()->id();
            $data['paid_amount'] = 0;
            $data['remaining_amount'] = (float) ($data['total_amount'] ?? 0);
            $data['payment_status'] = PaymentStatus::UNPAID;

            $invoice = Invoice::create($data);

            foreach ($items as $itemData) {
                $qty = round(max(0.01, (float) ($itemData['quantity'] ?? 1)), 2);
                $unitPrice = round(max(0.0, (float) ($itemData['unit_price'] ?? 0)), 2);
                $taxRate = round(max(0.0, (float) ($itemData['tax_rate'] ?? 0)), 2);
                $subtotal = round($qty * $unitPrice, 2);
                $taxAmount = round($subtotal * ($taxRate / 100), 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_name' => $itemData['item_name'] ?? 'Item',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total_amount' => round($subtotal + $taxAmount, 2),
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            static::recalculateTotals($invoice);
            static::activate($invoice->refresh(), $actor);

            OperationsAuditService::recordFor(
                model: $invoice,
                actionType: 'invoice_created',
                payload: [
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => (float) $invoice->total_amount,
                ],
                userId: $actor?->id ?? auth()->id(),
                companyId: (int) $invoice->company_id,
            );

            return $invoice->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $newData
     */
    public static function assertEditable(Invoice $invoice, array $newData = []): void
    {
        if ($invoice->isServiceMirror()) {
            throw ValidationException::withMessages([
                'invoice' => __('invoices.errors.service_mirror_locked'),
            ]);
        }

        if (! $invoice->hasFinancialImpact()) {
            return;
        }

        if (isset($newData['company_id']) && (int) $newData['company_id'] !== (int) $invoice->company_id) {
            throw ValidationException::withMessages([
                'company_id' => __('invoices.errors.cannot_modify_with_financial_records'),
            ]);
        }

        if (array_key_exists('customer_id', $newData) && (int) ($newData['customer_id'] ?? 0) !== (int) ($invoice->customer_id ?? 0)) {
            throw ValidationException::withMessages([
                'customer_id' => __('invoices.errors.cannot_modify_with_financial_records'),
            ]);
        }

        if (array_key_exists('employee_id', $newData) && (int) ($newData['employee_id'] ?? 0) !== (int) ($invoice->employee_id ?? 0)) {
            throw ValidationException::withMessages([
                'employee_id' => __('invoices.errors.cannot_modify_with_financial_records'),
            ]);
        }

        if (isset($newData['total_amount']) && round((float) $newData['total_amount'], 2) !== round((float) $invoice->total_amount, 2)) {
            throw ValidationException::withMessages([
                'total_amount' => __('invoices.errors.cannot_modify_with_financial_records'),
            ]);
        }

        if (array_key_exists('items', $newData) && is_array($newData['items'])) {
            $existingItems = $invoice->items()->orderBy('id')->get(['service_id', 'item_name', 'quantity', 'unit_price', 'tax_rate'])
                ->map(fn ($i) => [
                    'service_id' => $i->service_id ? (int) $i->service_id : null,
                    'item_name' => (string) $i->item_name,
                    'quantity' => round((float) $i->quantity, 2),
                    'unit_price' => round((float) $i->unit_price, 2),
                    'tax_rate' => round((float) $i->tax_rate, 2),
                ])
                ->values()
                ->all();

            $incomingItems = collect($newData['items'])
                ->map(fn ($i) => [
                    'service_id' => ! empty($i['service_id']) ? (int) $i['service_id'] : null,
                    'item_name' => (string) ($i['item_name'] ?? ''),
                    'quantity' => round((float) ($i['quantity'] ?? 0), 2),
                    'unit_price' => round((float) ($i['unit_price'] ?? 0), 2),
                    'tax_rate' => round((float) ($i['tax_rate'] ?? 0), 2),
                ])
                ->values()
                ->all();

            if ($existingItems !== $incomingItems) {
                throw ValidationException::withMessages([
                    'items' => __('invoices.errors.cannot_modify_with_financial_records'),
                ]);
            }
        }
    }

    public static function assertDeletable(Invoice $invoice): void
    {
        if ($invoice->isServiceMirror()) {
            throw ValidationException::withMessages([
                'invoice' => __('invoices.errors.service_mirror_locked'),
            ]);
        }

        if ($invoice->hasFinancialImpact()) {
            throw ValidationException::withMessages([
                'invoice' => __('invoices.errors.cannot_delete_with_financial_records'),
            ]);
        }
    }

    /**
     * Record the initial receipt when an invoice is created with payment enabled.
     *
     * @param  array<string, mixed>  $data
     */
    public static function recordCreationReceipt(Invoice $invoice, array $data, ?User $actor = null): ?FinancialReceipt
    {
        if (empty($data['is_paid_by_applicant']) || $invoice->isServiceMirror()) {
            return null;
        }

        if (FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $invoice->id)->exists()) {
            return null;
        }

        $due = round((float) $invoice->remaining_amount, 2);
        if ($due <= 0) {
            return null;
        }

        $rawAmount = $data['creation_received_amount'] ?? null;
        $amount = is_numeric($rawAmount) ? round((float) $rawAmount, 2) : $due;
        if ($amount <= 0) {
            return null;
        }

        $rawMethod = $data['creation_payment_method'] ?? FinancialPaymentMethod::CASH->value;
        $method = $rawMethod instanceof FinancialPaymentMethod ? $rawMethod->value : (string) $rawMethod;

        $requestKey = trim((string) ($data['creation_request_key'] ?? ''));
        if ($requestKey === '') {
            $requestKey = 'invoice-create-'.$invoice->id;
        }

        $collectorId = $invoice->creator?->employee?->id ?? $actor?->employee?->id;
        $payerName = $invoice->customer?->name ?? $invoice->employee?->user?->name;

        return FinancialReceiptService::recordFor(
            FinancialReceipt::SOURCE_INVOICE,
            (int) $invoice->id,
            [
                'request_key' => $requestKey,
                'amount' => $amount,
                'payment_method' => $method,
                'collector_employee_id' => $collectorId,
                'payer_name' => $payerName,
            ],
            $actor,
        );
    }
}
