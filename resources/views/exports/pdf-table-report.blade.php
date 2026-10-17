<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'تقرير' }}</title>
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 32mm;
            margin-bottom: 20mm;
            margin-left: 10mm;
            margin-right: 10mm;
        }
        body {
            font-family: 'dejavusans', 'sans-serif';
            direction: rtl;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #04386c;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #04386c;
            text-align: center;
        }
        .header-meta {
            font-size: 10px;
            color: #475569;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th {
            background-color: #04386c;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #04386c;
        }
        .data-table td {
            padding: 6px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            text-align: center;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 9px;
            color: #64748b;
        }
        .totals-card {
            margin-top: 15px;
            padding: 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <htmlpageheader name="page-header">
        <table class="header-table">
            <tr>
                <td style="width: 30%; text-align: right;" class="header-meta">
                    <strong>{{ $company_name ?? 'مجموعة فواز القابضة' }}</strong><br>
                    <span>نظام إدارة العمليات والموارد</span>
                </td>
                <td style="width: 40%; text-align: center;">
                    <div class="header-title">{{ $title }}</div>
                    @if(!empty($subtitle))
                        <div style="font-size: 11px; color: #475569; margin-top: 4px;">{{ $subtitle }}</div>
                    @endif
                </td>
                <td style="width: 30%; text-align: left;" class="header-meta">
                    <div>التاريخ: {{ $dual_date ?? \App\Services\HijriDateService::formatDual() }}</div>
                    <div>طُبع بواسطة: {{ $printed_by ?? (auth()->user()?->name ?? 'المسؤول') }}</div>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: right; width: 50%;">
                    كشف رسمي صادر من النظام الآلي — {{ $company_name ?? 'مجموعة فواز' }}
                </td>
                <td style="text-align: left; width: 50%;">
                    صفحة {PAGENO} من {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

    {{-- Content Table --}}
    <table class="data-table">
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align: center; padding: 20px; color: #64748b;">
                        لا توجد سجلات مطابقة للبيانات المحددة.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Summary or Totals Section if provided --}}
    @if(!empty($totals))
        <div class="totals-card">
            <table style="width: 100%;">
                <tr>
                    @foreach($totals as $label => $value)
                        <td style="text-align: center; padding: 5px;">
                            <span style="font-size: 10px; color: #475569;">{{ $label }}:</span>
                            <strong style="font-size: 12px; color: #04386c;">{{ $value }}</strong>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

</body>
</html>
