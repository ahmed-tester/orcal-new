<div class="md-root"
     wire:poll.10s
     x-data="{
        lightboxOpen: false,
        lightboxSrc: '',
        lightboxTitle: '',
        openLightbox(src, title) {
            this.lightboxSrc = src;
            this.lightboxTitle = title || '';
            this.lightboxOpen = true;
        },
        closeLightbox() {
            this.lightboxOpen = false;
        }
     }"
     @keydown.escape.window="closeLightbox()">
    <style>
        .md-root {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .md-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
            align-items: start;
        }
        @media (min-width: 1024px) {
            .md-grid {
                grid-template-columns: minmax(0, 2.1fr) minmax(290px, 1fr);
            }
        }
        .md-col-main,
        .md-col-side {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            min-width: 0;
        }
        .md-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .dark .md-card {
            background-color: #111827;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
        }
        .md-card-pad {
            padding: 1.25rem 1.5rem;
        }
        .md-msg-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }
        .dark .md-msg-header {
            background-color: rgba(31, 41, 55, 0.55);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        .md-flex-between {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .md-flex-wrap {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }
        .md-badge-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border-radius: 9999px;
            background-color: #082c50;
            color: #ffffff;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .md-badge-info {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 0.25rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .dark .md-badge-info {
            background-color: rgba(3, 105, 161, 0.25);
            color: #38bdf8;
        }
        .md-badge-muted {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.15rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .dark .md-badge-muted {
            background-color: #1f2937;
            color: #cbd5e1;
        }
        .md-date {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            border-radius: 0.5rem;
            padding: 0.25rem 0.65rem;
            font-size: 0.75rem;
            color: #64748b;
        }
        .md-date strong {
            color: #334155;
        }
        .dark .md-date {
            border-color: rgba(255, 255, 255, 0.1);
            background-color: #1f2937;
            color: #94a3b8;
        }
        .dark .md-date strong {
            color: #e2e8f0;
        }
        .md-subject {
            margin: 0.85rem 0 0;
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1.5;
            color: #0f172a;
        }
        .dark .md-subject {
            color: #f8fafc;
        }
        .md-sender-bar {
            margin-top: 1rem;
            padding-top: 0.85rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .dark .md-sender-bar {
            border-top-color: rgba(255, 255, 255, 0.08);
        }
        .md-sender-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }
        .md-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background-color: #082c50;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .md-sender-name {
            font-weight: 700;
            font-size: 0.925rem;
            color: #0f172a;
        }
        .dark .md-sender-name {
            color: #f8fafc;
        }
        .md-tag-client {
            border-radius: 9999px;
            background-color: #dbeafe;
            color: #1d4ed8;
            padding: 0.1rem 0.5rem;
            font-size: 0.68rem;
            font-weight: 700;
        }
        .dark .md-tag-client {
            background-color: rgba(29, 78, 216, 0.25);
            color: #93c5fd;
        }
        .md-sender-email {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.15rem;
        }
        .dark .md-sender-email {
            color: #94a3b8;
        }
        .md-via-pill {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
            border-radius: 0.625rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            color: #475569;
        }
        .dark .md-via-pill {
            border-color: rgba(255, 255, 255, 0.1);
            background-color: #1f2937;
            color: #cbd5e1;
        }
        .md-via-account {
            color: #082c50;
            font-weight: 600;
        }
        .dark .md-via-account {
            color: #60a5fa;
        }
        .md-email-canvas {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background-color: #ffffff;
            color: #1e293b;
            padding: 1.25rem;
            font-size: 0.875rem;
            line-height: 1.7;
            overflow-x: auto;
        }
        .dark .md-email-canvas {
            border-color: rgba(255, 255, 255, 0.12);
        }
        .md-email-canvas img {
            max-width: 100%;
            height: auto;
        }
        .md-email-canvas table {
            max-width: 100%;
        }
        .md-attachments-wrap {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        .dark .md-attachments-wrap {
            border-top-color: rgba(255, 255, 255, 0.08);
        }
        .md-att-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        @media (min-width: 640px) {
            .md-att-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .md-att-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            padding: 0.75rem;
            min-width: 0;
        }
        .dark .md-att-card {
            border-color: rgba(255, 255, 255, 0.1);
            background-color: rgba(31, 41, 55, 0.5);
        }
        .md-att-card--success {
            border-color: #bbf7d0;
            background-color: #f0fdf4;
        }
        .dark .md-att-card--success {
            border-color: rgba(34, 197, 94, 0.35);
            background-color: rgba(20, 83, 45, 0.25);
        }
        .md-att-card--info {
            border-color: #bae6fd;
            background-color: #f0f9ff;
        }
        .dark .md-att-card--info {
            border-color: rgba(56, 189, 248, 0.35);
            background-color: rgba(12, 74, 110, 0.25);
        }
        .md-att-thumb {
            width: 3rem;
            height: 3rem;
            flex-shrink: 0;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background-color: #f1f5f9;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .md-att-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .md-att-thumb--pdf {
            border-color: #fecaca;
            background-color: #fef2f2;
            color: #dc2626;
            font-size: 0.68rem;
            font-weight: 700;
            text-decoration: none;
        }
        .md-att-thumb--file {
            color: #64748b;
            text-decoration: none;
        }
        .md-att-info {
            min-width: 0;
            flex: 1;
        }
        .md-att-name {
            display: block;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: start;
            font-size: 0.78rem;
            font-weight: 700;
            color: #0f172a;
            background: transparent;
            border: 0;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
        }
        .dark .md-att-name {
            color: #f1f5f9;
        }
        .md-att-size {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 0.1rem;
        }
        .md-att-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
            margin-top: 0.25rem;
            font-size: 0.7rem;
        }
        .md-att-link-primary {
            font-weight: 600;
            color: #082c50;
            background: transparent;
            border: 0;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
        }
        .dark .md-att-link-primary {
            color: #60a5fa;
        }
        .md-att-link-muted {
            font-weight: 600;
            color: #475569;
            text-decoration: none;
        }
        .dark .md-att-link-muted {
            color: #cbd5e1;
        }
        .md-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 0.85rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .md-section-head {
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        .md-section-title {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }
        .dark .md-section-title {
            color: #f8fafc;
        }
        .md-pill-success {
            border-radius: 0.45rem;
            border: 1px solid #86efac;
            background-color: #f0fdf4;
            color: #15803d;
            padding: 0.2rem 0.55rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .dark .md-pill-success {
            border-color: rgba(34, 197, 94, 0.4);
            background-color: rgba(20, 83, 45, 0.3);
            color: #86efac;
        }
        .md-pill-info {
            border-radius: 0.45rem;
            border: 1px solid #7dd3fc;
            background-color: #f0f9ff;
            color: #0369a1;
            padding: 0.2rem 0.55rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .dark .md-pill-info {
            border-color: rgba(56, 189, 248, 0.4);
            background-color: rgba(12, 74, 110, 0.3);
            color: #7dd3fc;
        }
        .md-timeline-item {
            border-radius: 0.875rem;
            padding: 1rem;
            margin-bottom: 0.85rem;
        }
        .md-timeline-item--agent {
            border: 1.5px solid #bbf7d0;
            background-color: #f0fdf4;
        }
        .dark .md-timeline-item--agent {
            border-color: rgba(34, 197, 94, 0.35);
            background-color: rgba(20, 83, 45, 0.2);
        }
        .md-timeline-item--client {
            border: 1.5px solid #bae6fd;
            background-color: #f0f9ff;
        }
        .dark .md-timeline-item--client {
            border-color: rgba(56, 189, 248, 0.35);
            background-color: rgba(12, 74, 110, 0.2);
        }
        .md-timeline-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }
        .md-timeline-body {
            margin-top: 0.75rem;
            border-radius: 0.625rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #1e293b;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            line-height: 1.65;
            overflow-x: auto;
        }
        .md-empty-box {
            border-radius: 0.75rem;
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
            padding: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }
        .dark .md-empty-box {
            border-color: rgba(255, 255, 255, 0.12);
            background-color: rgba(31, 41, 55, 0.4);
            color: #94a3b8;
        }
        .md-notes-card {
            border-radius: 1rem;
            border: 1.5px solid #fde68a;
            background-color: #fffbeb;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }
        .dark .md-notes-card {
            border-color: rgba(245, 158, 11, 0.35);
            background-color: rgba(120, 53, 15, 0.2);
        }
        .md-notes-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #fde68a;
        }
        .dark .md-notes-head {
            border-bottom-color: rgba(245, 158, 11, 0.25);
        }
        .md-note-item {
            border-radius: 0.625rem;
            border: 1px solid #fde68a;
            background-color: #ffffff;
            padding: 0.75rem 1rem;
            margin-bottom: 0.6rem;
            font-size: 0.85rem;
        }
        .dark .md-note-item {
            border-color: rgba(245, 158, 11, 0.25);
            background-color: #111827;
        }
        .md-textarea {
            width: 100%;
            border-radius: 0.625rem;
            border: 1px solid #fcd34d;
            background-color: #ffffff;
            color: #0f172a;
            padding: 0.75rem;
            font-size: 0.85rem;
            box-sizing: border-box;
        }
        .dark .md-textarea {
            border-color: rgba(245, 158, 11, 0.35);
            background-color: #111827;
            color: #f8fafc;
        }
        .md-btn-warning {
            margin-top: 0.6rem;
            border: 0;
            border-radius: 0.625rem;
            background-color: #d97706;
            color: #ffffff;
            padding: 0.55rem 1rem;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }
        .md-btn-warning:hover {
            background-color: #b45309;
        }
        .md-btn-warning:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .md-dl {
            margin: 0;
            font-size: 0.85rem;
        }
        .md-dl-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.6rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .dark .md-dl-row {
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        .md-dl-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .md-dl-row dt {
            color: #64748b;
        }
        .dark .md-dl-row dt {
            color: #94a3b8;
        }
        .md-dl-row dd {
            margin: 0;
            font-weight: 600;
            color: #0f172a;
            text-align: end;
        }
        .dark .md-dl-row dd {
            color: #f1f5f9;
        }
        .md-status-form {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .md-status-field {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        .md-status-field label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
        }
        .dark .md-status-field label {
            color: #cbd5e1;
        }
        .md-select {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            background-color: #ffffff;
            color: #0f172a;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }
        .dark .md-select {
            border-color: rgba(255, 255, 255, 0.16);
            background-color: #1f2937;
            color: #f8fafc;
        }
        .md-checkbox-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.35rem 0;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }
        .dark .md-checkbox-row {
            color: #e2e8f0;
        }
        .md-status-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-top: 0.25rem;
        }
        .md-btn-primary {
            border: 0;
            border-radius: 0.625rem;
            background-color: #082c50;
            color: #ffffff;
            padding: 0.55rem 1rem;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }
        .md-btn-primary:hover {
            background-color: #0c3d6e;
        }
        .md-btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .md-btn-secondary {
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            background-color: #ffffff;
            color: #334155;
            padding: 0.5rem 0.9rem;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
        }
        .dark .md-btn-secondary {
            border-color: rgba(255, 255, 255, 0.16);
            background-color: #1f2937;
            color: #e2e8f0;
        }
        .md-ai-card {
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        .dark .md-ai-card {
            border-color: rgba(255, 255, 255, 0.08);
            background-color: #111827;
        }
        .md-ai-box {
            border-radius: 0.75rem;
            border: 1px solid #dbeafe;
            background-color: #eff6ff;
            padding: 0.85rem 1rem;
        }
        .dark .md-ai-box {
            border-color: rgba(59, 130, 246, 0.3);
            background-color: rgba(30, 58, 138, 0.2);
        }
        .md-ai-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #1e40af;
        }
        .dark .md-ai-label {
            color: #93c5fd;
        }
        .md-lightbox {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.9);
            padding: 1rem;
        }
        .md-lightbox-bar {
            display: flex;
            width: 100%;
            max-width: 64rem;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #ffffff;
            font-size: 0.875rem;
        }
        .md-lightbox-btn {
            border-radius: 0.5rem;
            background-color: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            padding: 0.4rem 0.85rem;
            font-size: 0.8rem;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
    </style>

    @php
        $timeline = collect();
        $threadMessages = $thread->getCollection();

        foreach ($threadMessages as $threadMessage) {
            if ($threadMessage->id !== $record->id) {
                $timeline->push([
                    'type' => 'client',
                    'name' => $threadMessage->sender_name ?: $threadMessage->sender_email,
                    'email' => $threadMessage->sender_email,
                    'date' => $threadMessage->received_at,
                    'body_html' => $threadMessage->sanitized_html,
                    'body_text' => null,
                    'attachments' => $threadMessage->regularAttachments,
                    'status' => $threadMessage->status?->getLabel(),
                ]);
            }

            foreach ($threadMessage->replies as $reply) {
                $timeline->push([
                    'type' => 'agent',
                    'name' => $reply->user?->name ?? __('mail_inbox.fields.team_reply'),
                    'email' => $threadMessage->recipient_email,
                    'date' => $reply->sent_at ?? $reply->created_at,
                    'body_html' => null,
                    'body_text' => $reply->body,
                    'attachments' => $reply->attachments,
                    'status' => $reply->status?->getLabel(),
                ]);
            }
        }

        $timeline = $timeline->sortBy(fn (array $item): int => $item['date']?->timestamp ?? 0)->values();
        $senderName = $record->sender_name ?: $record->sender_email;
        $senderInitial = mb_strtoupper(mb_substr(trim((string) $senderName), 0, 1));
    @endphp

    <div class="md-grid">
        <div class="md-col-main">
            <div class="md-card">
                <div class="md-msg-header">
                    <div class="md-flex-between">
                        <div class="md-flex-wrap">
                            <span class="md-badge-primary">
                                {{ __('mail_inbox.fields.original_message') }}
                            </span>
                            @if ($thread->total() > 1)
                                <span class="md-badge-info">
                                    {{ __('mail_inbox.fields.thread_badge', ['count' => $thread->total()]) }}
                                </span>
                            @endif
                        </div>
                        <div class="md-date">
                            <strong>{{ $record->received_at?->format('Y-m-d H:i') }}</strong>
                            <span>({{ $record->received_at?->diffForHumans() }})</span>
                        </div>
                    </div>

                    <h2 class="md-subject">{{ $record->subject }}</h2>

                    <div class="md-sender-bar">
                        <div class="md-sender-info">
                            <div class="md-avatar">{{ $senderInitial }}</div>
                            <div style="min-width: 0;">
                                <div class="md-flex-wrap">
                                    <span class="md-sender-name">{{ $senderName }}</span>
                                    <span class="md-tag-client">{{ __('mail_inbox.fields.client') }}</span>
                                </div>
                                @if (filled($record->sender_name) && $record->sender_name !== $record->sender_email)
                                    <div class="md-sender-email" dir="ltr">&lt;{{ $record->sender_email }}&gt;</div>
                                @endif
                            </div>
                        </div>
                        <div class="md-via-pill">
                            <strong>{{ __('mail_inbox.fields.received_via') }}:</strong>
                            <span dir="ltr" style="font-family: monospace;">{{ $record->recipient_email }}</span>
                            @if ($record->account?->name)
                                <span class="md-via-account">({{ $record->account->name }})</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="md-card-pad">
                    <div class="md-email-canvas">
                        {!! $record->sanitized_html !!}
                    </div>

                    @if ($record->regularAttachments->isNotEmpty())
                        <div class="md-attachments-wrap">
                            <div class="md-flex-between">
                                <h4 class="md-section-title">{{ __('mail_inbox.fields.attachments') }}</h4>
                                <span class="md-badge-muted">{{ $record->regularAttachments->count() }}</span>
                            </div>
                            <div class="md-att-grid">
                                @foreach ($record->regularAttachments as $attachment)
                                    @include('filament.mail.attachment-card', ['attachment' => $attachment, 'tone' => 'primary'])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="md-card md-card-pad">
                <div class="md-section-head">
                    <h3 class="md-section-title">
                        {{ __('mail_inbox.fields.timeline_title') }}
                        <span class="md-badge-muted">{{ $timeline->count() }}</span>
                    </h3>
                    <div class="md-flex-wrap">
                        <span class="md-pill-success">{{ __('mail_inbox.fields.team_replies') }}</span>
                        <span class="md-pill-info">{{ __('mail_inbox.fields.client_replies') }}</span>
                    </div>
                </div>

                @forelse ($timeline as $item)
                    @if ($item['type'] === 'agent')
                        <div class="md-timeline-item md-timeline-item--agent">
                            <div class="md-timeline-head">
                                <div style="min-width: 0;">
                                    <div class="md-flex-wrap">
                                        <strong style="color: #166534;">{{ $item['name'] }}</strong>
                                        <span style="border-radius: 9999px; background: #16a34a; color: #fff; padding: 0.1rem 0.5rem; font-size: 0.68rem; font-weight: 700;">{{ __('mail_inbox.fields.team_reply') }}</span>
                                    </div>
                                    <div style="font-size: 0.72rem; color: #15803d;" dir="ltr">{{ $item['email'] }}</div>
                                </div>
                                <div style="font-size: 0.75rem; color: #15803d;">
                                    {{ $item['date']?->format('Y-m-d H:i') }}
                                    @if ($item['status'])
                                        <span style="margin-inline-start: 0.5rem;">{{ $item['status'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="md-timeline-body" style="white-space: pre-line;">{{ $item['body_text'] }}</div>
                            @if ($item['attachments']->isNotEmpty())
                                <div class="md-att-grid">
                                    @foreach ($item['attachments'] as $attachment)
                                        @include('filament.mail.attachment-card', ['attachment' => $attachment, 'tone' => 'success'])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="md-timeline-item md-timeline-item--client">
                            <div class="md-timeline-head">
                                <div style="min-width: 0;">
                                    <div class="md-flex-wrap">
                                        <strong style="color: #075985;">{{ $item['name'] }}</strong>
                                        <span style="border-radius: 9999px; background: #0284c7; color: #fff; padding: 0.1rem 0.5rem; font-size: 0.68rem; font-weight: 700;">{{ __('mail_inbox.fields.client_message') }}</span>
                                    </div>
                                    <div style="font-size: 0.72rem; color: #0369a1;" dir="ltr">{{ $item['email'] }}</div>
                                </div>
                                <div style="font-size: 0.75rem; color: #0369a1;">{{ $item['date']?->format('Y-m-d H:i') }}</div>
                            </div>
                            <div class="md-timeline-body">
                                {!! $item['body_html'] !!}
                            </div>
                            @if ($item['attachments']->isNotEmpty())
                                <div class="md-att-grid">
                                    @foreach ($item['attachments'] as $attachment)
                                        @include('filament.mail.attachment-card', ['attachment' => $attachment, 'tone' => 'info'])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                @empty
                    <div class="md-empty-box">
                        {{ __('mail_inbox.fields.timeline_empty') }}
                    </div>
                @endforelse

                @if ($thread->hasPages())
                    <div style="margin-top: 1rem;">{{ $thread->links() }}</div>
                @endif
            </div>

            <div class="md-notes-card">
                <div class="md-notes-head">
                    <div>
                        <h3 class="md-section-title" style="color: #78350f;">{{ __('mail_inbox.fields.internal_notes') }}</h3>
                        <p style="margin: 0.25rem 0 0; font-size: 0.75rem; color: #b45309;">{{ __('mail_inbox.fields.internal_notes_hint') }}</p>
                    </div>
                    <span style="border-radius: 9999px; background: #d97706; color: #fff; padding: 0.2rem 0.65rem; font-size: 0.75rem; font-weight: 700;">{{ $record->internalNotes->count() }}</span>
                </div>

                @forelse ($record->internalNotes as $note)
                    <div class="md-note-item">
                        <div class="md-flex-between" style="font-size: 0.72rem; color: #64748b; margin-bottom: 0.35rem;">
                            <strong style="color: #b45309;">{{ $note->user?->name ?? __('mail_inbox.fields.team_reply') }}</strong>
                            <span>{{ $note->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        <p style="margin: 0; white-space: pre-line;">{{ $note->content }}</p>
                    </div>
                @empty
                    <p style="margin: 0 0 0.75rem; text-align: center; font-size: 0.78rem; color: #b45309;">{{ __('mail_inbox.fields.no_internal_notes') }}</p>
                @endforelse

                <div style="margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid rgba(217, 119, 6, 0.25);">
                    <textarea wire:model="newInternalNote"
                              rows="2"
                              placeholder="{{ __('mail_inbox.fields.note_placeholder') }}"
                              class="md-textarea"></textarea>
                    <button type="button"
                            wire:click="saveInternalNote"
                            wire:loading.attr="disabled"
                            class="md-btn-warning">
                        {{ __('mail_inbox.actions.save_note') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="md-col-side">
            <div class="md-card md-card-pad" id="mail-status-card">
                <div class="md-section-head">
                    <h3 class="md-section-title">{{ __('mail_inbox.fields.status_and_details') }}</h3>
                </div>
                @if (($isEditingStatus ?? false) && ($canReview ?? false))
                    <div class="md-status-form">
                        <div class="md-status-field">
                            <label for="md-review-status">{{ __('mail_inbox.fields.status') }}</label>
                            <select id="md-review-status" wire:model="reviewStatus" class="md-select">
                                @foreach (($statusOptions ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reviewStatus')
                                <span style="color: #dc2626; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="md-status-field">
                            <label for="md-review-priority">{{ __('mail_inbox.fields.priority') }}</label>
                            <select id="md-review-priority" wire:model="reviewPriority" class="md-select">
                                @foreach (($priorityOptions ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reviewPriority')
                                <span style="color: #dc2626; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="md-status-field">
                            <label for="md-review-category">{{ __('mail_inbox.fields.category') }}</label>
                            <select id="md-review-category" wire:model="reviewCategory" class="md-select">
                                @foreach (($categoryOptions ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reviewCategory')
                                <span style="color: #dc2626; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="md-checkbox-row" for="md-review-important">
                                <span>{{ __('mail_inbox.fields.is_important') }}</span>
                                <input id="md-review-important" type="checkbox" wire:model="reviewIsImportant">
                            </label>
                            <p style="margin: 0.15rem 0 0; font-size: 0.72rem; color: #64748b;">{{ __('mail_inbox.helpers.priority_vs_importance') }}</p>
                            @error('reviewIsImportant')
                                <span style="color: #dc2626; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="md-status-field">
                            <label for="md-review-assignee">{{ __('mail_inbox.fields.assignee') }}</label>
                            <select id="md-review-assignee" wire:model="reviewAssignedTo" class="md-select">
                                <option value="">{{ __('mail_inbox.fields.unassigned') }}</option>
                                @foreach (($assigneeOptions ?? []) as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('reviewAssignedTo')
                                <span style="color: #dc2626; font-size: 0.75rem;">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="md-status-actions">
                            <button type="button"
                                    wire:click="saveStatusReview"
                                    wire:loading.attr="disabled"
                                    class="md-btn-primary">
                                {{ __('mail_inbox.actions.save_status_changes') }}
                            </button>
                            <button type="button"
                                    wire:click="cancelStatusReview"
                                    wire:loading.attr="disabled"
                                    class="md-btn-secondary">
                                {{ __('mail_inbox.actions.cancel_edit_status') }}
                            </button>
                        </div>
                    </div>
                    <dl class="md-dl" style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;">
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.source_folder') }}</dt>
                            <dd>{{ $record->source_folder?->getLabel() ?? '-' }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.received_at') }}</dt>
                            <dd>{{ $record->received_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.replied_at') }}</dt>
                            <dd>{{ $record->replied_at?->format('Y-m-d H:i') ?? __('mail_inbox.fields.not_replied_yet') }}</dd>
                        </div>
                    </dl>
                @else
                    <dl class="md-dl">
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.status') }}</dt>
                            <dd>{{ $record->status?->getLabel() ?? '-' }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.priority') }}</dt>
                            <dd>{{ $record->priority?->getLabel() ?? '-' }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.category') }}</dt>
                            <dd>{{ $record->categoryLabel() }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.is_important') }}</dt>
                            <dd>{{ $record->is_important ? __('mail_inbox.fields.yes') : __('mail_inbox.fields.no') }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.assignee') }}</dt>
                            <dd>{{ $record->assignee?->name ?? __('mail_inbox.fields.unassigned') }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.source_folder') }}</dt>
                            <dd>{{ $record->source_folder?->getLabel() ?? '-' }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.received_at') }}</dt>
                            <dd>{{ $record->received_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                        </div>
                        <div class="md-dl-row">
                            <dt>{{ __('mail_inbox.fields.replied_at') }}</dt>
                            <dd>{{ $record->replied_at?->format('Y-m-d H:i') ?? __('mail_inbox.fields.not_replied_yet') }}</dd>
                        </div>
                    </dl>
                @endif
            </div>

            <div class="md-ai-card">
                <div class="md-section-head">
                    <h3 class="md-section-title">
                        {{ __('mail_inbox.fields.ai_analysis') }}
                    </h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.85rem;">
                    <div class="md-ai-box">
                        <div class="md-ai-label">{{ __('mail_inbox.fields.summary') }}</div>
                        <p style="margin: 0.35rem 0 0; white-space: pre-line; line-height: 1.65;">{{ $record->summary ?: __('mail_inbox.fields.no_summary') }}</p>
                    </div>
                    <div class="md-flex-between">
                        <span class="md-ai-label">{{ __('mail_inbox.fields.confidence') }}</span>
                        <span class="md-badge-primary">
                            {{ filled($record->confidence) ? $record->confidence.'%' : __('mail_inbox.fields.unknown') }}
                        </span>
                    </div>
                    <div>
                        <div class="md-ai-label">{{ __('mail_inbox.fields.classification_reason') }}</div>
                        <p style="margin: 0.3rem 0 0; line-height: 1.6;">{{ $record->classification_reason ?: __('mail_inbox.fields.no_classification_reason') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="lightboxOpen"
             x-transition.opacity
             class="md-lightbox"
             style="display: none;"
             @click.self="closeLightbox()">
            <div class="md-lightbox-bar">
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="lightboxTitle"></span>
                <div class="md-flex-wrap">
                    <a :href="lightboxSrc" target="_blank" class="md-lightbox-btn">
                        {{ __('mail_inbox.actions.open_file') }}
                    </a>
                    <button type="button" @click="closeLightbox()" class="md-lightbox-btn">
                        {{ __('mail_inbox.actions.close') }}
                    </button>
                </div>
            </div>
            <img :src="lightboxSrc" :alt="lightboxTitle" style="max-height: 80vh; max-width: 100%; border-radius: 0.5rem; object-fit: contain;">
        </div>
    </template>
</div>
