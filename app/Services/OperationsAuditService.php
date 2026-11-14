<?php

namespace App\Services;

use App\Models\ServiceTransaction;
use App\Models\UnifiedAuditLog;
use Illuminate\Database\Eloquent\Model;

class OperationsAuditService
{
    /**
     * Record an immutable audit log entry for a transaction or any auditable financial model.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function record(
        int $companyId,
        ?int $transactionId,
        string $actionType,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        array $payload = [],
        ?int $userId = null,
        ?Model $auditable = null,
    ): UnifiedAuditLog {
        $auditableType = $auditable ? $auditable->getMorphClass() : ($transactionId ? ServiceTransaction::class : null);
        $auditableId = $auditable ? $auditable->getKey() : $transactionId;

        $serviceTransactionId = null;
        if ($auditable instanceof ServiceTransaction) {
            $serviceTransactionId = $auditable->id;
        } elseif ($transactionId) {
            $serviceTransactionId = $transactionId;
        }

        return UnifiedAuditLog::create([
            'company_id' => $companyId,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'service_transaction_id' => $serviceTransactionId,
            'user_id' => $userId ?? auth()->id(),
            'action_type' => $actionType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Record an audit log directly for any model.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function recordFor(
        Model $model,
        string $actionType,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        array $payload = [],
        ?int $userId = null,
        ?int $companyId = null,
    ): UnifiedAuditLog {
        $resolvedCompanyId = $companyId ?? $model->company_id ?? 1;

        return static::record(
            companyId: (int) $resolvedCompanyId,
            transactionId: $model instanceof ServiceTransaction ? $model->id : null,
            actionType: $actionType,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            payload: $payload,
            userId: $userId,
            auditable: $model,
        );
    }

    /**
     * Bulk record multiple audit log entries efficiently in a single query.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function recordBulk(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $now = now();
        $userId = auth()->id();
        $ip = request()->ip();
        $ua = request()->userAgent();

        $records = [];
        foreach ($rows as $row) {
            $auditable = $row['auditable'] ?? null;
            $auditableType = $auditable ? $auditable->getMorphClass() : ($row['auditable_type'] ?? null);
            $auditableId = $auditable ? $auditable->getKey() : ($row['auditable_id'] ?? null);

            $txId = $row['service_transaction_id'] ?? null;
            if ($auditable instanceof ServiceTransaction) {
                $txId = $auditable->id;
            }

            $records[] = [
                'company_id' => $row['company_id'] ?? 1,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'service_transaction_id' => $txId,
                'user_id' => $row['user_id'] ?? $userId,
                'action_type' => $row['action_type'],
                'old_status' => $row['old_status'] ?? null,
                'new_status' => $row['new_status'] ?? null,
                'payload' => isset($row['payload']) ? json_encode($row['payload'], JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $row['ip_address'] ?? $ip,
                'user_agent' => $row['user_agent'] ?? $ua,
                'created_at' => $now,
            ];
        }

        UnifiedAuditLog::insert($records);

        return count($records);
    }
}

