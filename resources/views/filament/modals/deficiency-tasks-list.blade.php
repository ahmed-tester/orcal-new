@php
    $openTasks = $tasks->where('status', 'open');
    $resolvedTasks = $tasks->where('status', 'resolved');
@endphp

<div style="font-family: inherit; display: flex; flex-direction: column; gap: 14px;">
    @if($openTasks->isEmpty() && $resolvedTasks->isEmpty())
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; text-align: center; color: #166534; font-weight: 600;">
            ✓ {{ __('services_tracking.reconciliation.no_open_tasks') }}
        </div>
    @else
        {{-- Open tasks section --}}
        @if($openTasks->isNotEmpty())
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="font-size: 13px; font-weight: 700; color: #b91c1c; display: flex; align-items: center; gap: 6px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                    {{ __('services_tracking.reconciliation.open_tasks_count', ['count' => $openTasks->count()]) }}
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach($openTasks as $task)
                        <div style="background: #fff; border: 1px solid #fecaca; border-radius: 10px; padding: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; gap: 8px;">
                                <div style="font-weight: 700; font-size: 13px; color: #1f2937;">
                                    {{ $task->title }}
                                </div>
                                <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                    {{ $task->task_code }}
                                </span>
                            </div>

                            @if($task->description)
                                <div style="font-size: 12px; color: #4b5563; margin-bottom: 8px; line-height: 1.4;">
                                    {{ $task->description }}
                                </div>
                            @endif

                            <div style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 11px; color: #6b7280; border-top: 1px solid #f3f4f6; pt: 6px; padding-top: 6px;">
                                <div>
                                    <span style="font-weight: 600;">{{ __('services_tracking.reconciliation.task_responsible') }}:</span>
                                    <span>{{ $task->responsibleEmployee?->user?->name ?? '—' }}</span>
                                </div>
                                @if($task->due_date)
                                    <div>
                                        <span style="font-weight: 600;">{{ __('services_tracking.reconciliation.task_due_date') }}:</span>
                                        <span>{{ $task->due_date->format('Y-m-d') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Resolved tasks section --}}
        @if($resolvedTasks->isNotEmpty())
            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 6px;">
                <div style="font-size: 12px; font-weight: 700; color: #047857; display: flex; align-items: center; gap: 6px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                    {{ __('services_tracking.reconciliation.status_resolved') }} ({{ $resolvedTasks->count() }})
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    @foreach($resolvedTasks as $task)
                        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; font-size: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-weight: 600; color: #374151;">{{ $task->title }}</span>
                                <span style="color: #059669; font-weight: 700; font-size: 11px;">✓ {{ __('services_tracking.reconciliation.status_resolved') }}</span>
                            </div>
                            @if($task->resolution_notes)
                                <div style="color: #6b7280; font-size: 11px; margin-top: 2px;">
                                    {{ $task->resolution_notes }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>

