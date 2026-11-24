<?php

namespace App\Services\Financial;

use App\Models\FinancialReceipt;
use App\Services\Financial\Contracts\FinancialReceiptSourceConnector;
use Illuminate\Validation\ValidationException;

class FinancialReceiptConnectorRegistry
{
    /**
     * @var array<string, class-string<FinancialReceiptSourceConnector>>
     */
    private static array $connectors = [
        FinancialReceipt::SOURCE_SERVICE_TRANSACTION => ServiceTransactionReceiptConnector::class,
        FinancialReceipt::SOURCE_INVOICE => InvoiceReceiptConnector::class,
        FinancialReceipt::SOURCE_PAYROLL_COLLECTION => PayrollCollectionReceiptConnector::class,
    ];

    public static function register(string $sourceType, string $connectorClass): void
    {
        static::$connectors[$sourceType] = $connectorClass;
    }

    public static function has(string $sourceType): bool
    {
        return isset(static::$connectors[$sourceType]);
    }

    public static function for(string $sourceType): FinancialReceiptSourceConnector
    {
        $class = static::$connectors[$sourceType] ?? null;

        if (! $class || ! class_exists($class)) {
            throw ValidationException::withMessages([
                'source_type' => __('financial.errors.unsupported_source'),
            ]);
        }

        return app($class);
    }
}
