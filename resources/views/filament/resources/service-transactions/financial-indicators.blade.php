@php
    $columns = [
        'company' => ['received', 'custody', 'treasury_received', 'fee_paid', 'fee_funded_remaining', 'reimbursed', 'employee_due', 'surplus'],
        'service' => ['received', 'custody', 'treasury_received', 'fee_paid', 'fee_funded_remaining', 'reimbursed', 'employee_due', 'surplus'],
        'collector' => ['cash_received', 'custody', 'treasury_received'],
        'deliverer' => ['treasury_received'],
        'employee' => ['fee_paid', 'reimbursed', 'employee_due'],
    ];
@endphp

<div class="service-financial-indicators">
    <style>
        .service-financial-report,
        .service-financial-report .financial-report-grid {
            display: grid;
            gap: 1rem;
        }

        .service-financial-report .financial-report-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
            margin: 0;
        }

        .service-financial-report label {
            display: grid;
            gap: .375rem;
        }

        .service-financial-report .financial-report-apply {
            align-self: end;
        }

        .service-financial-report dd {
            margin: .375rem 0 0;
            font-size: 1.25rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .service-financial-indicators {
            --indicator-border: #e2e8f0;
            --indicator-header: #f1f5f9;
            --indicator-muted: #475569;
            display: grid;
            gap: 1.25rem;
            min-width: 0;
        }

        .dark .service-financial-indicators {
            --indicator-border: #334155;
            --indicator-header: #1e293b;
            --indicator-muted: #cbd5e1;
        }

        .service-financial-indicators p {
            margin: 0;
            color: var(--indicator-muted);
            font-size: .875rem;
        }

        .service-financial-indicators section {
            min-width: 0;
            border: 1px solid var(--indicator-border);
            border-radius: .75rem;
        }

        .service-financial-indicators h3 {
            margin: 0;
            padding: .75rem 1rem;
            font-size: 1rem;
            font-weight: 700;
        }

        .service-financial-indicators section > p {
            padding: 0 1rem .75rem;
        }

        .service-financial-indicators .indicator-table {
            overflow-x: auto;
            border-radius: 0 0 .75rem .75rem;
        }

        .service-financial-indicators table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }

        .service-financial-indicators thead {
            background-color: var(--indicator-header);
        }

        .service-financial-indicators th,
        .service-financial-indicators td {
            padding: .625rem .875rem;
            border-block-start: 1px solid var(--indicator-border);
            text-align: start;
            vertical-align: top;
        }

        .service-financial-indicators th:first-child {
            min-width: 12rem;
            width: 26%;
            overflow-wrap: anywhere;
        }

        .service-financial-indicators th:not(:first-child),
        .service-financial-indicators td {
            min-width: 6rem;
            text-align: end;
        }

        .service-financial-indicators td {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
    </style>

    @if ($groups !== null)
        <p>{{ __('financial.indicators.description') }}</p>
        @foreach ($columns as $group => $metrics)
            <section>
                <h3>{{ __('financial.indicators.groups.'.$group) }}</h3>
                @if ($groups[$group] === [])
                    <p>{{ __('financial.indicators.empty') }}</p>
                @else
                    <div class="indicator-table" tabindex="0" role="region" aria-label="{{ __('financial.indicators.groups.'.$group) }}">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('financial.indicators.name') }}</th>
                                    @foreach ($metrics as $metric)
                                        <th scope="col">{{ __('financial.summary.'.$metric) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups[$group] as $row)
                                    <tr>
                                        <th scope="row">{{ $row['name'] ?? '—' }}</th>
                                        @foreach ($metrics as $metric)
                                            <td><bdi dir="ltr">{{ number_format($row['amounts'][$metric] ?? 0, 2) }}</bdi></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    @endif
</div>
