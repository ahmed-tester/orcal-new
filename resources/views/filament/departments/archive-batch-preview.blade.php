<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60">
        <div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('organization.departments.deletion.archive.columns.root') }}:</span>
            <span class="font-semibold">{{ $batch->root_department_name }}</span>
            @if ($batch->root_department_code)
                <span class="ms-1 rounded bg-gray-200 px-1.5 py-0.5 text-xs dark:bg-gray-700">{{ $batch->root_department_code }}</span>
            @endif
        </div>
        <div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('organization.departments.deletion.archive.columns.actor') }}:</span>
            <span class="font-medium">{{ $batch->actor?->name ?? '-' }}</span>
        </div>
        @if ($batch->target_department_name)
            <div class="col-span-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('organization.departments.deletion.archive.columns.target') }}:</span>
                <span class="font-medium">{{ $batch->target_department_name }}</span>
            </div>
        @endif
    </div>

    <div class="space-y-2">
        @foreach ($groupedItems as $type => $items)
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('organization.departments.deletion.archive.item_types.' . $type) }} ({{ $items->count() }})
                </div>
                <ul class="mt-2 max-h-40 space-y-1 overflow-y-auto text-xs text-gray-700 dark:text-gray-300">
                    @foreach ($items as $item)
                        <li class="flex items-center justify-between border-b border-gray-100 pb-1 last:border-b-0 dark:border-gray-800">
                            <span>{{ $item->previous_state['name'] ?? ('#' . $item->item_id) }}</span>
                            @if (! empty($item->previous_state['department_code']))
                                <span class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-800">{{ $item->previous_state['department_code'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
