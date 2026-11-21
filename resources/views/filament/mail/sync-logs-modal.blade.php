<div class="space-y-2 text-sm">
    @forelse ($logs as $log)
        <div class="flex items-center justify-between rounded-lg border p-3 dark:border-gray-700">
            <div>
                <div class="font-semibold">{{ $log->folder }} — {{ $log->status?->getLabel() }}</div>
                <div class="text-xs text-gray-500">
                    {{ __('mail_inbox.fields.messages_found') }}: {{ $log->messages_found }} |
                    {{ __('mail_inbox.fields.messages_imported') }}: {{ $log->messages_imported }} |
                    {{ __('mail_inbox.fields.messages_rejected') }}: {{ $log->messages_rejected }}
                </div>
                @if ($log->error_message)
                    <div class="mt-1 text-xs text-danger-600">{{ $log->error_message }}</div>
                @endif
            </div>
            <div class="text-xs text-gray-400">
                {{ $log->started_at?->format('Y-m-d H:i') }}
            </div>
        </div>
    @empty
        <div class="py-4 text-center text-gray-500">-</div>
    @endforelse
</div>
