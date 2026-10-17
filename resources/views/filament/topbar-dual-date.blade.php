@php
    $today = now();
    $hijriService = app(\App\Services\HijriDateService::class);
    $dualDate = $hijriService->formatDual($today, true);
    $days = [
        'Sunday' => 'الأحد',
        'Monday' => 'الاثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
        'Saturday' => 'السبت',
    ];
    $dayName = $days[$today->format('l')] ?? '';
@endphp

<div class="hidden lg:flex items-center gap-2 px-3 py-1.5 mx-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/90 text-xs text-slate-700 dark:text-slate-200 font-medium shadow-sm transition-all hover:border-primary-500">
    <span class="text-base">📅</span>
    <span class="font-bold text-primary-700 dark:text-primary-300">{{ $dayName }}</span>
    <span class="text-slate-300 dark:text-slate-600">|</span>
    <span>{{ $dualDate }}</span>
</div>
