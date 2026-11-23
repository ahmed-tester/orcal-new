<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;

/**
 * One-off, reviewed removal. Query-builder deletes intentionally bypass model
 * immutability guards for these exact records only. No constraints are disabled.
 */
function removeServiceTransaction12(Connection $db, string $backupDirectory, bool $execute): array
{
    $ensure = static function (bool $condition, string $message): void {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    };
    $decode = static fn ($value): array => json_decode($value ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    $cents = static function ($value): int {
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', (string) $value, $parts)) {
            throw new RuntimeException('Unexpected money value.');
        }

        return ($parts[1] === '-' ? -1 : 1) * ((int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0'));
    };
    $money = static fn (int $value): string => ($value < 0 ? '-' : '').intdiv(abs($value), 100).'.'.str_pad((string) (abs($value) % 100), 2, '0', STR_PAD_LEFT);

    return $db->transaction(function () use ($db, $backupDirectory, $execute, $ensure, $decode, $cents, $money): array {
        $schema = $db->getSchemaBuilder();
        $ensure($db->table('companies')->where('id', 19)->lockForUpdate()->exists(), 'Company 19 missing.');
        $plan = [];
        foreach (['service_transactions' => 12, 'financial_receipts' => 8, 'cash_handovers' => 6, 'cash_handover_items' => 6, 'invoices' => 17, 'debts' => 10, 'treasury_transactions' => 353] as $table => $id) {
            $row = $db->table($table)->where('id', $id)->lockForUpdate()->first();
            $ensure($row !== null && (int) $row->company_id === 19, "Missing or different company: {$table}:{$id}");
            $plan[$table] = [(array) $row];
        }
        $transaction = $plan['service_transactions'][0];
        $receipt = $plan['financial_receipts'][0];
        $handover = $plan['cash_handovers'][0];
        $item = $plan['cash_handover_items'][0];
        $movement = $plan['treasury_transactions'][0];
        $metadata = $decode($transaction['metadata']);
        $ensure($transaction['transaction_number'] === 'SRV-C19-0002' && $cents($transaction['total_amount']) === 75000 && $cents($transaction['paid_amount']) === 75000, 'Transaction identity/amount changed.');
        $ensure((int) ($metadata['auto_invoice_id'] ?? 0) === 17 && (int) ($metadata['auto_debt_id'] ?? 0) === 10, 'Invoice/debt links changed.');
        $ensure($receipt['source_type'] === 'service_transaction' && (int) $receipt['source_id'] === 12 && $receipt['status'] === 'confirmed' && $receipt['payment_method'] === 'cash' && $cents($receipt['amount']) === 75000, 'Receipt changed.');
        $ensure($handover['status'] === 'confirmed' && (int) $handover['treasury_transaction_id'] === 353 && $cents($handover['total_confirmed_amount']) === 75000 && $cents($handover['total_requested_amount']) === 75000 && $cents($handover['difference_amount']) === 0, 'Handover changed.');
        $ensure((int) $item['cash_handover_id'] === 6 && (int) $item['financial_receipt_id'] === 8 && $cents($item['confirmed_amount']) === 75000 && $cents($item['requested_amount']) === 75000, 'Handover item changed.');
        $movementMeta = $decode($movement['metadata']);
        $ensure($movement['transaction_type'] === 'incoming' && $cents($movement['amount']) === 75000 && ($movementMeta['source'] ?? '') === 'cash_handover' && (int) ($movementMeta['cash_handover_id'] ?? 0) === 6, 'Treasury link changed.');
        foreach (['invoices', 'debts'] as $table) {
            $ensure($cents($plan[$table][0]['total_amount']) === 75000 && $cents($plan[$table][0]['paid_amount']) === 75000, "{$table} amount changed.");
        }
        $debtMeta = $decode($plan['debts'][0]['metadata']);
        $ensure(($debtMeta['source'] ?? '') === 'service_transaction' && (int) ($debtMeta['service_transaction_id'] ?? 0) === 12, 'Debt identity changed.');
        $ensure(str_contains($plan['invoices'][0]['notes'] ?? '', 'SRV-C19-0002'), 'Invoice identity changed.');
        $ensure($db->table('financial_receipts')->where('source_type', 'service_transaction')->where('source_id', 12)->count() === 1, 'Additional receipts found.');
        $ensure(! $db->table('financial_disbursements')->where('source_type', 'service_transaction')->where('source_id', 12)->exists(), 'Disbursements found.');
        $ensure($db->table('cash_handover_items')->where('cash_handover_id', 6)->count() === 1 && $db->table('cash_handover_items')->where('financial_receipt_id', 8)->count() === 1, 'Shared/additional handover items found.');
        $ensure(! $db->table('invoice_items')->where('invoice_id', 17)->exists() && ! $db->table('debt_collections')->where('debt_id', 10)->exists(), 'Invoice items/debt collections found.');

        $targets = ['App\\Models\\ServiceTransaction' => [12], 'App\\Models\\FinancialReceipt' => [8], 'App\\Models\\CashHandover' => [6], 'App\\Models\\Invoice' => [17], 'App\\Models\\Debt' => [10], 'App\\Models\\TreasuryTransaction' => [353]];
        $matches = static fn ($type, $id): bool => is_string($type) && isset($targets[$type]) && ctype_digit((string) $id) && in_array((int) $id, $targets[$type], true);
        foreach (['unified_deficiency_tasks' => 'taskable', 'unified_audit_logs' => 'auditable'] as $table => $prefix) {
            $rows = $db->table($table)->where(function ($query) use ($targets, $prefix) {
                $query->where('service_transaction_id', 12);
                foreach ($targets as $type => $ids) {
                    $query->orWhere(fn ($nested) => $nested->where($prefix.'_type', $type)->whereIn($prefix.'_id', $ids));
                }
            })->lockForUpdate()->get();
            foreach ($rows as $row) {
                $ensure((int) $row->company_id === 19, "Unexpected company in {$table}.");
                $ensure($row->service_transaction_id === null || (int) $row->service_transaction_id === 12, "Shared record in {$table}.");
                $ensure($row->{$prefix.'_type'} === null || $matches($row->{$prefix.'_type'}, $row->{$prefix.'_id'}), "Conflicting polymorphic link in {$table}.");
            }
            $plan[$table] = $rows->map(fn ($row) => (array) $row)->all();
            if ($table === 'unified_deficiency_tasks' && $plan[$table] !== []) {
                $targets['App\\Models\\UnifiedDeficiencyTask'] = array_column($plan[$table], 'id');
                $matches = static fn ($type, $id): bool => is_string($type) && isset($targets[$type]) && ctype_digit((string) $id) && in_array((int) $id, $targets[$type], true);
            }
        }
        foreach (['notifications', 'jobs', 'failed_jobs'] as $table) {
            $plan[$table] = [];
            if (! $schema->hasTable($table)) {
                continue;
            }
            foreach ($db->table($table)->orderBy('id')->cursor() as $row) {
                $selected = false;
                if ($table === 'notifications') {
                    $data = $decode($row->data);
                    $selected = $matches($data['record_type'] ?? null, $data['record_id'] ?? null);
                } else {
                    $payload = $decode($row->payload);
                    $command = $payload['data']['command'] ?? null;
                    if (is_string($command) && str_contains($command, 'App\\Notifications\\BaseSystemNotification')) {
                        $job = (array) unserialize($command, ['allowed_classes' => false]);
                        if (($job['__PHP_Incomplete_Class_Name'] ?? '') === 'Illuminate\\Notifications\\SendQueuedNotifications') {
                            $notification = (array) ($job['notification'] ?? []);
                            $selected = ($notification['__PHP_Incomplete_Class_Name'] ?? '') === 'App\\Notifications\\BaseSystemNotification'
                                && $matches($notification['recordType'] ?? null, $notification['recordId'] ?? null);
                        }
                    }
                }
                if ($selected) {
                    $locked = $db->table($table)->where('id', $row->id)->lockForUpdate()->first();
                    $ensure($locked !== null && (array) $locked === (array) $row, "Concurrent change in {$table}; stop workers and retry.");
                    $ensure($table !== 'jobs' || $row->reserved_at === null, 'A related job is reserved; stop workers and wait before retrying.');
                    $plan[$table][] = (array) $row;
                }
            }
        }

        // Check real schema references, including CASCADE and SET NULL edges.
        // Also inspect conventional links without foreign-key constraints.
        $direct = ['service_transaction_id' => [12], 'allocated_service_transaction_id' => [12], 'cash_handover_id' => [6], 'financial_receipt_id' => [8], 'invoice_id' => [17], 'debt_id' => [10], 'treasury_transaction_id' => [353]];
        foreach ($schema->getTables() as $info) {
            $table = $info['name'];
            $columns = $schema->getColumnListing($table);
            $checks = [];
            foreach ($schema->getForeignKeys($table) as $fk) {
                if (isset($plan[$fk['foreign_table']]) && $plan[$fk['foreign_table']] !== []) {
                    $ensure(count($fk['columns']) === 1 && $fk['foreign_columns'] === ['id'], "Unsupported composite reference in {$table}.");
                    $checks[$fk['columns'][0]] = array_column($plan[$fk['foreign_table']], 'id');
                }
            }
            foreach ($direct as $column => $ids) {
                if (in_array($column, $columns, true)) {
                    $checks[$column] = $ids;
                }
            }
            $assertPlanned = static function ($query) use ($plan, $table, $columns, $ensure): void {
                $ids = array_column($plan[$table] ?? [], 'id');
                if ($ids !== [] && in_array('id', $columns, true)) {
                    $query->whereNotIn('id', $ids);
                }
                $ensure(! $query->lockForUpdate()->exists(), "Unexpected reference in {$table}; nothing deleted.");
            };
            foreach ($checks as $column => $ids) {
                $assertPlanned($db->table($table)->whereIn($column, $ids));
            }
            foreach ($columns as $column) {
                if (! str_ends_with($column, '_type')) {
                    continue;
                }
                $idColumn = substr($column, 0, -5).'_id';
                if (! in_array($idColumn, $columns, true)) {
                    continue;
                }
                foreach ($targets + ['service_transaction' => [12], 'cash_handover' => [6]] as $type => $ids) {
                    $assertPlanned($db->table($table)->where($column, $type)->whereIn($idColumn, $ids));
                }
            }
            if (in_array('metadata', $columns, true)) {
                foreach ($direct + ['auto_invoice_id' => [17], 'auto_debt_id' => [10]] as $key => $ids) {
                    $assertPlanned($db->table($table)->whereIn('metadata->'.$key, $ids));
                }
            }
            if ($execute && isset($plan[$table]) && $plan[$table] !== [] && $db->getDriverName() === 'mysql') {
                $ensure(strtolower($info['engine'] ?? '') === 'innodb', "Nontransactional table: {$table}.");
            }
        }
        // Preserve the historical opening balance; validate continuity from the
        // previous movement, then subtract exactly 750 from subsequent balances.
        $previous = $db->table('treasury_transactions')->where('company_id', 19)->where('id', '<', 353)->orderByDesc('id')->lockForUpdate()->first();
        $ensure($previous !== null, 'Cannot establish the preceding treasury balance.');
        $running = $cents($previous->balance_after);
        $later = $db->table('treasury_transactions')->where('company_id', 19)->where('id', '>=', 353)->orderBy('id')->lockForUpdate()->get();
        $balanceUpdates = [];
        foreach ($later as $row) {
            $ensure(in_array($row->transaction_type, ['incoming', 'outgoing'], true), 'Unknown treasury movement type.');
            $ensure($cents($row->amount) >= 0, 'Negative treasury amount.');
            $running += ($row->transaction_type === 'incoming' ? 1 : -1) * $cents($row->amount);
            $ensure($running === $cents($row->balance_after), "Existing balance inconsistency at treasury movement {$row->id}; nothing deleted.");
            if ((int) $row->id !== 353) {
                $balanceUpdates[] = ['id' => $row->id, 'balance_after' => $money($running - 75000)];
            }
        }
        $summary = ['executed' => false, 'records' => array_map('count', $plan), 'later_balances_to_update' => count($balanceUpdates), 'final_balance_before' => $money($running), 'final_balance_after' => $money($running - 75000)];
        if (! $execute) {
            return $summary;
        }
        $ensure($db->getDriverName() !== 'mysql' || strtolower(collect($schema->getTables())->firstWhere('name', 'companies')['engine'] ?? '') === 'innodb', 'Company table must be transactional.');
        $ensure(is_dir($backupDirectory) || mkdir($backupDirectory, 0700, true), 'Cannot create private backup directory.');
        $backup = $backupDirectory.'/transaction-12-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6)).'.json';
        $handle = fopen($backup, 'x');
        $ensure($handle !== false, 'Cannot create backup file.');
        try {
            $ensure(chmod($backup, 0600), 'Cannot secure backup permissions.');
            $json = json_encode(['created_at' => gmdate(DATE_ATOM), 'database' => $db->getDatabaseName(), 'delete_records' => $plan, 'treasury_before' => $later->map(fn ($row) => (array) $row)->all(), 'previous_treasury' => (array) $previous, 'balance_updates' => $balanceUpdates], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $ensure(fwrite($handle, $json) === strlen($json) && fflush($handle), 'Backup write failed.');
            $ensure(fsync($handle), 'Backup sync failed.');
        } finally {
            fclose($handle);
        }
        foreach (['notifications', 'jobs', 'failed_jobs', 'unified_audit_logs', 'unified_deficiency_tasks', 'cash_handover_items', 'cash_handovers', 'financial_receipts', 'invoices', 'debts', 'treasury_transactions', 'service_transactions'] as $table) {
            $ids = array_column($plan[$table], 'id');
            if ($ids !== []) {
                $ensure($db->table($table)->whereIn('id', $ids)->delete() === count($ids), "Delete count changed for {$table}.");
            }
        }
        foreach ($balanceUpdates as $update) {
            $ensure($db->table('treasury_transactions')->where('company_id', 19)->where('id', $update['id'])->update(['balance_after' => $update['balance_after']]) === 1, 'Treasury update failed.');
        }
        foreach ($plan as $table => $rows) {
            if ($rows !== []) {
                $ensure(! $db->table($table)->whereIn('id', array_column($rows, 'id'))->exists(), "Post-delete verification failed for {$table}.");
            }
        }
        $ensure($cents($db->table('treasury_transactions')->where('company_id', 19)->orderByDesc('id')->value('balance_after')) === $running - 75000, 'Final treasury verification failed.');
        $summary['executed'] = true;
        $summary['backup'] = $backup;

        return $summary;
    });
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
    return;
}
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
try {
    $app->make(Kernel::class)->bootstrap();
    $execute = in_array('--execute', $argv, true);
    if ($execute && ! $app->isDownForMaintenance()) {
        throw new RuntimeException('Run php artisan down and stop queue workers before --execute.');
    }
    $db = $app->make('db')->connection();
    if ($db->getDriverName() !== 'mysql') {
        throw new RuntimeException('This production entry point requires MySQL.');
    }
    $summary = removeServiceTransaction12($db, dirname(base_path()).'/orcal-maintenance-backups', $execute);
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'STOPPED: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
