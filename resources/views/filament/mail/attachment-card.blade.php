@php
    $toneClass = match ($tone ?? 'primary') {
        'success' => 'md-att-card--success',
        'info' => 'md-att-card--info',
        default => '',
    };
@endphp

<div class="md-att-card {{ $toneClass }}">
    @if ($attachment->is_image)
        <button type="button"
                @click='openLightbox(@json($attachment->url), @json($attachment->file_name))'
                class="md-att-thumb"
                style="cursor: pointer;"
                title="{{ __('mail_inbox.actions.preview_image') }}">
            <img src="{{ $attachment->url }}" alt="{{ $attachment->file_name }}">
        </button>
    @elseif ($attachment->is_pdf)
        <a href="{{ $attachment->url }}" target="_blank" rel="noopener"
           class="md-att-thumb md-att-thumb--pdf"
           title="{{ __('mail_inbox.actions.open_file') }}">
            PDF
        </a>
    @else
        <a href="{{ $attachment->download_url }}" target="_blank" rel="noopener"
           class="md-att-thumb md-att-thumb--file"
           title="{{ __('mail_inbox.actions.download_file') }}">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </a>
    @endif

    <div class="md-att-info">
        @if ($attachment->is_image)
            <button type="button"
                    @click='openLightbox(@json($attachment->url), @json($attachment->file_name))'
                    class="md-att-name"
                    title="{{ $attachment->file_name }}">
                {{ $attachment->file_name }}
            </button>
        @else
            <a href="{{ $attachment->url }}" target="_blank" rel="noopener"
               class="md-att-name"
               title="{{ $attachment->file_name }}">
                {{ $attachment->file_name }}
            </a>
        @endif
        <div class="md-att-size">{{ $attachment->formatted_size }}</div>
        <div class="md-att-actions">
            @if ($attachment->is_image)
                <button type="button" @click='openLightbox(@json($attachment->url), @json($attachment->file_name))'
                        class="md-att-link-primary">
                    {{ __('mail_inbox.actions.preview_image') }}
                </button>
            @else
                <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="md-att-link-primary">
                    {{ __('mail_inbox.actions.open_file') }}
                </a>
            @endif
            <a href="{{ $attachment->download_url }}" download="{{ $attachment->file_name }}" target="_blank" rel="noopener"
               class="md-att-link-muted">
                {{ __('mail_inbox.actions.download_file') }}
            </a>
        </div>
    </div>
</div>
