<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SupervisionShift;
use App\Services\ExportPdfService;
use App\Services\HijriDateService;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class SupervisionShiftPrintController extends Controller
{
    /**
     * Preview / Print single supervision shift sheet.
     */
    public function print(SupervisionShift $shift)
    {
        $shift->load(['company', 'supervisor.user', 'supervisor.jobTitle', 'supervisor.department', 'task', 'creator']);
        return view('supervision-shifts.print', compact('shift'));
    }

    /**
     * Export / Download single supervision shift sheet as PDF.
     */
    public function pdf(Request $request, SupervisionShift $shift)
    {
        $shift->load(['company', 'supervisor.user', 'supervisor.jobTitle', 'supervisor.department', 'task', 'creator']);
        $html = view('supervision-shifts.print', compact('shift'))->render();
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFonts  = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'margin_top'       => 10,
            'margin_bottom'    => 10,
            'margin_left'      => 12,
            'margin_right'     => 12,
            'default_font'     => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'autoArabic'       => true,
            'baseScript'       => 1,
            'biDirectional'    => true,
            'fontDir'          => array_merge($defaultConfig['fontDir'], [
                storage_path('fonts'),
            ]),
            'fontdata'         => $defaultFonts['fontdata'],
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;

        $mpdf->WriteHTML($html);

        $filename = 'supervision-shift-' . $shift->id . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export general supervision shifts statement table report.
     */
    public function statement(Request $request)
    {
        $query = SupervisionShift::with(['company', 'supervisor.user', 'supervisor.jobTitle', 'task', 'creator'])
            ->orderBy('id', 'desc');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->query('company_id'));
        }

        $shifts = $query->get();

        $headers = [
            __('tasks.supervision_shifts.print.col_index'),
            __('tasks.supervision_shifts.fields.type'),
            __('tasks.supervision_shifts.fields.supervisor'),
            __('tasks.supervision_shifts.fields.company'),
            __('tasks.supervision_shifts.fields.assigned_employees'),
            __('tasks.supervision_shifts.fields.task_and_description'),
            __('tasks.supervision_shifts.fields.duration'),
            __('tasks.supervision_shifts.fields.daily_hours'),
            __('tasks.supervision_shifts.fields.status'),
        ];

        $companyTitle = null;
        if ($request->filled('company_id')) {
            $companyTitle = Company::find($request->query('company_id'))?->trade_name;
        }

        $rows = $shifts->map(function ($shift, $i) {
            $typeLabel = __('tasks.supervision_shifts.types.' . $shift->type) ?? $shift->type;
            $typeBadge = $shift->type === 'shift'
                ? "<span style=\"color:#d97706; font-weight:bold;\">{$typeLabel}</span>"
                : "<span style=\"color:#0284c7; font-weight:bold;\">{$typeLabel}</span>";

            $supervisorName = $shift->supervisor?->user?->name ?? '—';
            if ($shift->supervisor?->jobTitle?->name) {
                $supervisorName .= '<br><small style="color:#64748b;">' . $shift->supervisor->jobTitle->name . '</small>';
            }

            $ids = $shift->assigned_employees ?? [];
            if (!empty($ids)) {
                $names = Employee::whereIn('id', $ids)->with('user')->get()->pluck('user.name')->filter()->toArray();
                $teamCount = count($names);
                $teamPreview = implode('، ', array_slice($names, 0, 2)) . ($teamCount > 2 ? '...' : '');
                $teamText = "<strong>{$teamCount}</strong>: {$teamPreview}";
            } else {
                $teamText = '—';
            }

            $taskTitle = $shift->title ?: ($shift->task?->name ?? '—');
            if ($shift->tasks) {
                $taskTitle .= '<br><small style="color:#64748b;">' . \Illuminate\Support\Str::limit($shift->tasks, 35) . '</small>';
            }

            $duration = '—';
            if ($shift->start_date && $shift->end_date) {
                $duration = $shift->start_date->format('Y/m/d') . ' ➔ ' . $shift->end_date->format('Y/m/d');
            } elseif ($shift->start_date) {
                $duration = __('tasks.supervision_shifts.table.from_prefix', ['val' => $shift->start_date->format('Y/m/d')]);
            }

            $hours = '—';
            if ($shift->start_time && $shift->end_time) {
                $hours = substr($shift->start_time, 0, 5) . ' ➔ ' . substr($shift->end_time, 0, 5);
            } elseif ($shift->start_time) {
                $hours = __('tasks.supervision_shifts.table.from_prefix', ['val' => substr($shift->start_time, 0, 5)]);
            }

            $st = $shift->status;
            $stLabel = __('tasks.supervision_shifts.statuses.' . $st) ?? $st;
            $statusBadge = match ($st) {
                'active' => "<span style=\"color:#16a34a; font-weight:bold;\">{$stLabel}</span>",
                'completed' => "<span style=\"color:#0284c7; font-weight:bold;\">{$stLabel}</span>",
                'suspended' => "<span style=\"color:#d97706; font-weight:bold;\">{$stLabel}</span>",
                default => "<span style=\"color:#dc2626; font-weight:bold;\">{$stLabel}</span>",
            };

            return [
                $i + 1,
                $typeBadge,
                $supervisorName,
                $shift->company?->trade_name ?? '—',
                $teamText,
                $taskTitle,
                $duration,
                $hours,
                $statusBadge,
            ];
        })->toArray();

        return ExportPdfService::exportTableReport(
            title: __('tasks.supervision_shifts.print.statement_title'),
            headers: $headers,
            rows: $rows,
            options: [
                'orientation'  => 'L',
                'company_name' => $companyTitle,
                'subtitle'     => __('tasks.supervision_shifts.print.statement_subtitle'),
                'totals'       => [
                    __('tasks.supervision_shifts.print.total_shifts_count') => count($rows),
                ],
                'filename'     => 'supervision-shifts-statement-' . date('Ymd-His'),
            ]
        );
    }
}
