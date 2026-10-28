<?php

namespace App\Services;

use App\Models\DebtCollection;
use App\Models\InstallmentPayment;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UniversalPrintService
{
    /**
     * Render and return a printable document response based on type and ID.
     */
    public static function render(string $type, $id, array $params = []): Response
    {
        return match ($type) {
            'installment-payment', 'installment_payment' => self::renderInstallmentPayment($id, $params),
            'debt-collection', 'debt_collection' => self::renderDebtCollection($id, $params),
            default => throw new NotFoundHttpException(__('المستند المطلوب غير معرف في محرك الطباعة الموحد.')),
        };
    }

    /**
     * Render an official voucher for an Installment Payment.
     */
    protected static function renderInstallmentPayment($id, array $params = []): Response
    {
        $payment = InstallmentPayment::with([
            'company',
            'employee.user',
            'installment.installmentType',
            'installment.employee.user',
            'schedule',
            'executor.user',
        ])->findOrFail($id);

        return ExportPdfService::exportVoucher($payment);
    }

    /**
     * Render an official voucher for a Debt Collection.
     */
    protected static function renderDebtCollection($id, array $params = []): Response
    {
        $collection = DebtCollection::with([
            'company',
            'employee.user',
            'debt.employee.user',
            'debt.company',
            'executor.user',
        ])->findOrFail($id);

        return ExportPdfService::exportVoucher($collection);
    }
}
