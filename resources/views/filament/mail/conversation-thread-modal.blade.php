<div class="space-y-4 text-sm">
    @forelse ($thread as $message)
        <div @class([
            'rounded-xl border p-4',
            'border-primary-400 bg-primary-50/60 dark:border-primary-700 dark:bg-primary-950/30' => $message->id === $currentId,
            'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => $message->id !== $currentId,
        ])>
            <div class="flex flex-wrap items-start justify-between gap-3 border-b pb-3 dark:border-gray-700">
                <div class="min-w-0">
                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $message->subject }}</div>
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        {{ __('mail_inbox.fields.sender') }}:
                        {{ $message->sender_name ?: $message->sender_email }}
                        @if ($message->sender_name)
                            &lt;{{ $message->sender_email }}&gt;
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ __('mail_inbox.fields.to') }}: {{ $message->recipient_email }}
                    </div>
                </div>
                <div class="text-end text-xs text-gray-500">
                    <div>{{ $message->received_at?->format('Y-m-d H:i') }}</div>
                    <div class="mt-1">{{ $message->status?->getLabel() }}</div>
                </div>
            </div>

            <div class="prose mt-3 max-w-none text-sm dark:prose-invert">
                {!! $message->sanitized_html !!}
            </div>

            @if ($message->regularAttachments->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2 border-t pt-3 dark:border-gray-700">
                    @foreach ($message->regularAttachments as $attachment)
                        <a href="{{ $attachment->download_url }}"
                           class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <span>{{ $attachment->file_name }}</span>
                            <span class="text-gray-400">({{ $attachment->formatted_size }})</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @foreach ($message->replies as $reply)
                <div class="mt-3 rounded-lg border border-success-200 bg-success-50 p-3 dark:border-success-800 dark:bg-success-950/30">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-success-700 dark:text-success-300">
                        <span>
                            {{ $reply->user?->name ?? __('mail_inbox.fields.team_reply') }}
                            &rarr; {{ $reply->to_email }}
                        </span>
                        <span>{{ $reply->sent_at?->format('Y-m-d H:i') ?? $reply->status?->getLabel() }}</span>
                    </div>
                    <div class="mt-2 whitespace-pre-line">{{ $reply->body }}</div>
                    @foreach ($reply->attachments as $attachment)
                        <a class="mt-1 inline-block text-xs text-primary-600 underline" href="{{ $attachment->download_url }}">
                            {{ $attachment->file_name }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    @empty
        <p class="text-center text-sm text-gray-500">{{ __('mail_inbox.modal.empty_thread') }}</p>
    @endforelse

    @if ($thread->hasPages())
        <div>{{ $thread->links() }}</div>
    @endif
</div>
