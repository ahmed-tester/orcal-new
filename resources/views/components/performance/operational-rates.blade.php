@props([
    'onTime' => null,
    'taskCompletion' => null,
    'onTimeAction' => null,
    'taskCompletionAction' => null,
    'showHints' => false,
    'precision' => 0,
])

<div {{ $attributes->class(['perf-operational-rates']) }}>
    <x-performance.rate-ring
        :rate="$onTime"
        :label="__('performance.columns.on_time_rate')"
        :sublabel="$onTime === null ? __('performance.cards.no_data') : ($showHints ? __('performance.cards.on_time_rate_hint') : null)"
        :action="$onTimeAction"
        :precision="$precision"
    />
    <x-performance.rate-ring
        :rate="$taskCompletion"
        :label="__('performance.columns.task_completion_rate')"
        :sublabel="$taskCompletion === null ? __('performance.cards.no_data') : ($showHints ? __('performance.cards.tasks_completion_rate_hint') : null)"
        :action="$taskCompletionAction"
        :precision="$precision"
    />
</div>
