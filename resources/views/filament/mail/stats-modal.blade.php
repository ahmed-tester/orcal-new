@php
    $cards = [
        ['key' => 'today', 'value' => $stats['today'], 'color' => 'primary'],
        ['key' => 'reply_rate', 'value' => $stats['reply_rate'].'%', 'color' => 'success'],
        ['key' => 'avg_reply_hours', 'value' => $stats['avg_reply_hours'], 'color' => 'info'],
        ['key' => 'urgent_unanswered', 'value' => $stats['urgent_unanswered'], 'color' => 'danger'],
        ['key' => 'review', 'value' => $stats['review'], 'color' => 'warning'],
        ['key' => 'connected_accounts', 'value' => $stats['connected_accounts'], 'color' => 'success'],
        ['key' => 'total', 'value' => $stats['total'], 'color' => 'gray'],
        ['key' => 'unread', 'value' => $stats['unread'], 'color' => 'warning'],
        ['key' => 'important', 'value' => $stats['important'], 'color' => 'danger'],
    ];
@endphp

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($cards as $card)
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs font-semibold text-gray-500">{{ __('mail_inbox.stats.'.$card['key']) }}</div>
            <div @class([
                'mt-2 text-2xl font-bold',
                'text-primary-600 dark:text-primary-400' => $card['color'] === 'primary',
                'text-success-600 dark:text-success-400' => $card['color'] === 'success',
                'text-info-600 dark:text-info-400' => $card['color'] === 'info',
                'text-danger-600 dark:text-danger-400' => $card['color'] === 'danger',
                'text-warning-600 dark:text-warning-400' => $card['color'] === 'warning',
                'text-gray-700 dark:text-gray-300' => $card['color'] === 'gray',
            ])>{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>
