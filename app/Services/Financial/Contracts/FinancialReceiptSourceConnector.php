<?php

namespace App\Services\Financial\Contracts;

use App\Enums\PaymentItemCategory;
use App\Models\FinancialReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface FinancialReceiptSourceConnector
{
    public function sourceType(): string;

    public function find(int $sourceId): Model;

    public function findLocked(int $sourceId): Model;

    public function assertManaged(Model $source): void;

    public function authorize(Model $source, string $action, ?User $user = null): User;

    public function companyId(Model $source): int;

    public function branchId(Model $source): ?int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function defaultCollectorEmployeeId(Model $source, User $user, array $data): ?int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function defaultPayerName(Model $source, array $data): ?string;

    public function handoverCategory(): PaymentItemCategory;

    public function validateRecordAmount(Model $source, float $amount): void;

    public function validateConfirmAmount(Model $source, FinancialReceipt $receipt): void;

    public function onReceiptRecorded(Model $source, FinancialReceipt $receipt, User $user): void;

    public function onReceiptConfirmed(Model $source, FinancialReceipt $receipt, User $user): void;

    public function onReceiptReversed(Model $source, FinancialReceipt $receipt, string $reason, ?User $user = null): void;

    public function onHandoverChanged(Model $source): void;
}
