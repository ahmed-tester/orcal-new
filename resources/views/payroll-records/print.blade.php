<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ $record->operation_type === 'collection' ? __('finance.payroll_records.sheet.print_collection_title') : __('finance.payroll_records.sheet.print_payroll_title') }}
        - {{ $record->employee?->user?->name ?? '' }} ({{ \Carbon\Carbon::parse($record->payroll_month)->format('Y-m') }})
    </title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #0f172a;
            direction: rtl;
            padding: 20px;
        }

        .no-print-bar {
            max-width: 1100px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background-color: #1e3a8a;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1e40af;
        }
        .btn-pdf {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-pdf:hover {
            background-color: #0369a1;
        }
        .btn-close {
            background-color: #e2e8f0;
            color: #475569;
        }
        .btn-close:hover {
            background-color: #cbd5e1;
        }

        #printable-sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px 24px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.05);
        }

        /* Printable Header */
        .print-header {
            border-bottom: 2px solid #2e3882;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .print-header-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Table */
        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 8px;
        }
        .payroll-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 6px;
            border: 1px solid #1e293b;
            font-size: 11px;
            text-align: center;
            font-weight: 700;
        }
        .payroll-table td {
            color: #0f172a;
            padding: 7px 6px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            text-align: center;
            background-color: #ffffff;
        }
        .payroll-table tfoot td {
            border: 1px solid #94a3b8;
            background-color: #f8fafc;
        }

        /* Print Media Query */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            body * { visibility: hidden !important; }
            #printable-sheet, #printable-sheet * { visibility: visible !important; }
            #printable-sheet {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: #ffffff !important;
                color: #000000 !important;
                padding: 6mm 8mm !important;
                box-sizing: border-box !important;
            }
            .no-print-bar { display: none !important; }
            .no-print { display: none !important; }
            .payroll-table th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 6px 4px !important;
                border: 1px solid #1e293b !important;
                font-size: 11px !important;
                text-align: center !important;
            }
            .payroll-table td {
                color: #000000 !important;
                background-color: #ffffff !important;
                padding: 5px 4px !important;
                border: 1px solid #475569 !important;
                font-size: 10.5px !important;
                text-align: center !important;
            }
            .payroll-table tfoot td {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Action Bar --}}
    <div class="no-print-bar">
        <div style="display: flex; gap: 8px; align-items: center;">
            <button class="action-btn btn-print" onclick="window.print()">
                🖨️ {{ __('finance.payroll_records.slip.print_btn') }}
            </button>
            <a href="{{ route('payroll-records.pdf', $record) }}" class="action-btn btn-pdf">
                📄 {{ __('finance.payroll_records.slip.pdf_btn') }}
            </a>
        </div>
        <button class="action-btn btn-close" onclick="window.close()">
            ✖ {{ __('finance.payroll_records.slip.close_btn') }}
        </button>
    </div>

    {{-- Main Printable Sheet Area --}}
    <div id="printable-sheet">

        {{-- Formal Printable Header (Identical to Sheet Print Header) --}}
        <div class="print-header">
            <div class="print-header-grid">
                <div style="text-align: right;">
                    <div style="font-size: 15px; font-weight: 900; color: #1e3a8a;">
                        {{ $record->company?->trade_name ?? __('finance.payroll_records.sheet.print_default_company') }}
                    </div>
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 3px;">
                        {{ __('finance.payroll_records.sheet.print_dept') }}
                    </div>
                </div>

                <div style="text-align: center;">
                    <div style="font-size: 18px; font-weight: 900; color: #2e3882; text-decoration: underline;">
                        @if($record->operation_type === 'collection')
                            {{ __('finance.payroll_records.sheet.print_collection_title') }}
                        @else
                            {{ __('finance.payroll_records.sheet.print_payroll_title') }}
                        @endif
                    </div>
                    <div style="font-size: 11px; font-weight: bold; color: #334155; margin-top: 4px;">
                        {{ __('finance.payroll_records.sheet.print_month_label', ['month' => \Carbon\Carbon::parse($record->payroll_month)->format('Y-m')]) }}
                        <span style="color: #64748b;">— (1 {{ __('finance.payroll_records.sheet.employees_unit') }})</span>
                    </div>
                </div>

                <div style="text-align: left; font-size: 10.5px; color: #475569;">
                    <div><strong>{{ __('finance.payroll_records.sheet.print_date_label') }}</strong> {{ \App\Services\HijriDateService::formatDual(now(), true) }}</div>
                    <div style="margin-top: 3px;"><strong>{{ __('finance.payroll_records.sheet.print_user_label') }}</strong> {{ auth()->user()?->name ?? __('finance.payroll_records.sheet.print_default_user') }}</div>
                </div>
            </div>
        </div>

        {{-- The Table (Clean Neutral Colors) --}}
        @php
            $basicAmount = (float) $record->basic_salary;
            $additionAmount = (float) $record->additional_amount;
            $deductionAmount = (float) $record->deduction_amount;
            $netAmount = (float) $record->net_amount;
            $paidAmount = (float) $record->paid_amount;
            $remainingAmount = (float) $record->remaining_amount;

            $identity = $record->employee?->details?->national_id
                ?? $record->employee?->details?->iqama_number
                ?? '—';

            $iqamaExpiry = null;
            if (!empty($record->employee?->details?->iqama_expiry)) {
                try {
                    $iqamaExpiry = \Carbon\Carbon::parse($record->employee->details->iqama_expiry)->format('Y-m-d');
                } catch (\Exception) {}
            }
        @endphp

        <table class="payroll-table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 45px;">{{ __('finance.payroll_records.sheet.serial') }}</th>
                    <th style="text-align: right; min-width: 160px;">{{ __('finance.payroll_records.sheet.col_name') }}</th>
                    <th style="text-align: center; width: 110px;">{{ __('finance.payroll_records.sheet.col_identity') }}</th>
                    <th style="text-align: center; min-width: 110px;">{{ __('finance.payroll_records.sheet.col_company') }}</th>
                    <th style="text-align: center; width: 90px;">{{ __('finance.payroll_records.sheet.col_basic') }}</th>
                    <th style="text-align: center; width: 80px;">{{ __('finance.payroll_records.sheet.col_additions') }}</th>
                    <th style="text-align: center; width: 80px;">{{ __('finance.payroll_records.sheet.col_deductions') }}</th>
                    <th style="text-align: center; width: 100px;">{{ __('finance.payroll_records.sheet.col_net') }}</th>
                    <th style="text-align: center; width: 95px;">{{ __('finance.payroll_records.sheet.col_paid') }}</th>
                    <th style="text-align: center; width: 95px;">{{ __('finance.payroll_records.sheet.col_remaining') }}</th>
                    <th style="text-align: center; width: 100px;">{{ __('finance.payroll_records.sheet.col_phone') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    {{-- 1. Serial --}}
                    <td style="text-align: center; font-weight: 700; font-family: monospace; color: #475569;">
                        1.
                    </td>

                    {{-- 2. Employee Name --}}
                    <td style="text-align: right;">
                        <div style="font-weight: 700; font-size: 0.85rem; color: #0f172a;">
                            {{ $record->employee?->user?->name ?? '—' }}
                        </div>
                        <div class="no-print" style="font-size: 0.7rem; color: #64748b; margin-top: 0.15rem; display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                            <span style="font-family: monospace; background: #e2e8f0; padding: 0.05rem 0.3rem; border-radius: 0.25rem; font-weight: 600;">🆔 {{ $record->employee?->employee_number }}</span>
                            @if(!empty($record->record_number))
                                <span style="color: #475569; font-weight: 700; direction: ltr; display: inline-block;">{{ $record->record_number }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- 3. National ID / Iqama Number --}}
                    <td style="text-align: center;">
                        <div style="font-family: monospace; font-weight: 700; color: #0f172a;">{{ $identity }}</div>
                        @if(!empty($iqamaExpiry))
                            <div style="font-size: 0.68rem; color: #64748b; font-family: monospace;">
                                {{ __('finance.payroll_records.sheet.expiry_prefix') }} {{ $iqamaExpiry }}
                            </div>
                        @endif
                    </td>

                    {{-- 4. Company (Clean Text) --}}
                    <td style="text-align: center; font-size: 0.8rem; font-weight: 600; color: #0f172a;">
                        {{ $record->company?->trade_name ?? $record->employee?->company?->trade_name ?? '—' }}
                    </td>

                    {{-- 5. Basic Salary --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: #0f172a;">
                        {{ number_format($basicAmount, 2) }}
                    </td>

                    {{-- 6. Additions --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: #0f172a;">
                        {{ $additionAmount > 0 ? number_format($additionAmount, 2) : '0.00' }}
                    </td>

                    {{-- 7. Deductions --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 700; font-size: 0.825rem; color: #0f172a;">
                        {{ $deductionAmount > 0 ? number_format($deductionAmount, 2) : '0.00' }}
                    </td>

                    {{-- 8. Net Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 800; font-size: 0.875rem; color: #0f172a;">
                        {{ number_format($netAmount, 2) }}
                    </td>

                    {{-- 9. Paid Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 800; font-size: 0.875rem; color: #0f172a;">
                        {{ number_format($paidAmount, 2) }}
                    </td>

                    {{-- 10. Remaining Amount --}}
                    <td style="text-align: center; font-family: monospace; font-weight: 800; font-size: 0.875rem; color: #0f172a;">
                        {{ number_format($remainingAmount, 2) }}
                    </td>

                    {{-- 11. Phone --}}
                    <td style="text-align: center; font-family: monospace; direction: ltr; font-size: 0.775rem; color: #475569;">
                        {{ $record->employee?->user?->phone ?? '—' }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc;">
                    <td colspan="4" style="text-align: right; padding: 0.85rem 1rem; font-size: 0.875rem; color: #0f172a; font-weight: 800;">
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                            <span>📊</span>
                            <span>{{ __('finance.payroll_records.sheet.total_general', ['count' => 1]) }}</span>
                        </span>
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #0f172a; font-weight: 800;">
                        {{ number_format($basicAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #0f172a; font-weight: 800;">
                        {{ number_format($additionAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #0f172a; font-weight: 800;">
                        {{ number_format($deductionAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.875rem; color: #0f172a; font-weight: 800;">
                        {{ number_format($netAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.875rem; color: #0f172a; font-weight: 800;">
                        {{ number_format($paidAmount, 2) }}
                    </td>
                    <td style="text-align: center; font-family: monospace; font-size: 0.875rem; color: #0f172a; font-weight: 800;">
                        {{ number_format($remainingAmount, 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    </div>

</body>
</html>
