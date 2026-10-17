<x-filament-panels::page>
    @php
        $user = auth()->user();
        $isManager = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_tickets'));
        $ticket = $this->record;
    @endphp

    <style>
        .tkt-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            font-family: inherit;
        }

        /* Card container */
        .tkt-card {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .dark .tkt-card {
            background-color: #111827;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        /* Header section */
        .tkt-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 1.25rem;
        }
        .dark .tkt-header {
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        @media (min-width: 768px) {
            .tkt-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .tkt-title-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .tkt-number-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 700;
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .dark .tkt-number-badge {
            background-color: rgba(3, 105, 161, 0.2);
            color: #38bdf8;
            border-color: rgba(56, 189, 248, 0.3);
        }

        .tkt-subject {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .dark .tkt-subject {
            color: #f8fafc;
        }

        .tkt-badges-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Badges */
        .tkt-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid transparent;
        }
        .tkt-badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
        }

        /* Status colors */
        .tkt-status-open { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .dark .tkt-status-open { background: rgba(29, 78, 216, 0.2); color: #60a5fa; border-color: rgba(96, 165, 250, 0.3); }
        .tkt-dot-open { background: #2563eb; }

        .tkt-status-progress { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .dark .tkt-status-progress { background: rgba(180, 83, 9, 0.2); color: #fbbf24; border-color: rgba(251, 191, 36, 0.3); }
        .tkt-dot-progress { background: #f59e0b; }

        .tkt-status-waiting { background: #faf5ff; color: #7e22ce; border-color: #e9d5ff; }
        .dark .tkt-status-waiting { background: rgba(126, 34, 206, 0.2); color: #c084fc; border-color: rgba(192, 132, 252, 0.3); }
        .tkt-dot-waiting { background: #a855f7; }

        .tkt-status-resolved { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .dark .tkt-status-resolved { background: rgba(21, 128, 61, 0.2); color: #4ade80; border-color: rgba(74, 222, 128, 0.3); }
        .tkt-dot-resolved { background: #22c55e; }

        .tkt-status-closed { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .dark .tkt-status-closed { background: rgba(71, 85, 105, 0.2); color: #94a3b8; border-color: rgba(148, 163, 184, 0.3); }
        .tkt-dot-closed { background: #64748b; }

        /* Priority colors */
        .tkt-priority-urgent { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .dark .tkt-priority-urgent { background: rgba(185, 28, 28, 0.2); color: #f87171; border-color: rgba(248, 113, 113, 0.3); }

        .tkt-priority-high { background: #fff7ed; color: #c2410c; border-color: #ffedd5; }
        .dark .tkt-priority-high { background: rgba(194, 65, 12, 0.2); color: #fb923c; border-color: rgba(251, 146, 60, 0.3); }

        .tkt-priority-medium { background: #f0f9ff; color: #0369a1; border-color: #e0f2fe; }
        .dark .tkt-priority-medium { background: rgba(3, 105, 161, 0.2); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); }

        .tkt-priority-low { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
        .dark .tkt-priority-low { background: rgba(71, 85, 105, 0.2); color: #94a3b8; border-color: rgba(148, 163, 184, 0.3); }

        .tkt-cat-badge { background: #f1f5f9; color: #334155; }
        .dark .tkt-cat-badge { background: #1e293b; color: #cbd5e1; }

        /* Metadata Grid */
        .tkt-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            padding-top: 1.25rem;
        }
        @media (min-width: 768px) {
            .tkt-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .tkt-meta-label {
            font-size: 0.75rem;
            color: #64748b;
            display: block;
            margin-bottom: 0.25rem;
        }
        .dark .tkt-meta-label {
            color: #94a3b8;
        }
        .tkt-meta-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
        }
        .dark .tkt-meta-value {
            color: #f1f5f9;
        }

        /* Description Box */
        .tkt-desc-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            font-size: 0.925rem;
            line-height: 1.7;
            color: #334155;
            white-space: pre-line;
        }
        .dark .tkt-desc-box {
            background-color: rgba(17, 24, 39, 0.6);
            border-color: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
        }

        /* Avatar */
        .tkt-avatar {
            width: 42px;
            height: 42px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .tkt-avatar-creator {
            background: linear-gradient(135deg, #082c50, #0284c7);
            color: #ffffff;
        }
        .tkt-avatar-staff {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
        }
        .tkt-avatar-note {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #ffffff;
        }

        /* Conversation Thread */
        .tkt-thread-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 1rem 0;
        }
        .dark .tkt-thread-title {
            color: #f8fafc;
        }

        .tkt-msg-card {
            border-radius: 1rem;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }
        .dark .tkt-msg-card {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .tkt-msg-staff {
            background: #f0f7ff;
            border-color: #bfdbfe;
        }
        .dark .tkt-msg-staff {
            background: rgba(14, 116, 144, 0.12);
            border-color: rgba(56, 189, 248, 0.25);
        }

        .tkt-msg-note {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .dark .tkt-msg-note {
            background: rgba(180, 83, 9, 0.12);
            border-color: rgba(251, 191, 36, 0.25);
        }

        .tkt-msg-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.85rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tkt-author-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tkt-author-name {
            font-weight: 700;
            font-size: 0.925rem;
            color: #0f172a;
        }
        .dark .tkt-author-name {
            color: #f8fafc;
        }

        .tkt-author-role-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
        }

        .tkt-msg-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .tkt-msg-body {
            font-size: 0.925rem;
            line-height: 1.7;
            color: #334155;
            white-space: pre-line;
            padding-right: 3.25rem;
        }
        .dark .tkt-msg-body {
            color: #cbd5e1;
        }

        /* Attachments */
        .tkt-attachments-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid #f1f5f9;
        }
        .dark .tkt-attachments-wrap {
            border-top-color: rgba(255, 255, 255, 0.08);
        }

        .tkt-attach-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0284c7;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .dark .tkt-attach-link {
            background: #1e293b;
            border-color: #334155;
            color: #38bdf8;
        }
        .tkt-attach-link:hover {
            border-color: #0284c7;
            background: #f0f9ff;
        }
        .dark .tkt-attach-link:hover {
            background: #0f172a;
        }

        /* Reply Form Area */
        .tkt-reply-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .tkt-reply-card {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .tkt-reply-textarea {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            padding: 0.85rem 1rem;
            font-size: 0.925rem;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
            resize: vertical;
        }
        .dark .tkt-reply-textarea {
            background-color: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }
        .tkt-reply-textarea:focus {
            border-color: #082c50;
            box-shadow: 0 0 0 3px rgba(8, 44, 80, 0.15);
        }
        .dark .tkt-reply-textarea:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .tkt-reply-footer {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }
        @media (min-width: 640px) {
            .tkt-reply-footer {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .tkt-status-select {
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            padding: 0.45rem 0.85rem;
            font-size: 0.825rem;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
        }
        .dark .tkt-status-select {
            background-color: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        .tkt-submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.75rem;
            border-radius: 0.75rem;
            font-size: 0.925rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #082c50, #0c4a6e);
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(8, 44, 80, 0.2);
            transition: all 0.2s ease;
        }
        .tkt-submit-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .tkt-submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .tkt-internal-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.825rem;
            font-weight: 600;
            color: #b45309;
            background-color: #fffbeb;
            padding: 0.4rem 0.85rem;
            border-radius: 0.5rem;
            border: 1px solid #fde68a;
            user-select: none;
        }
        .dark .tkt-internal-checkbox {
            background-color: rgba(180, 83, 9, 0.2);
            color: #fbbf24;
            border-color: rgba(251, 191, 36, 0.3);
        }

        .tkt-closed-box {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 1rem;
            border: 1px dashed #cbd5e1;
            color: #64748b;
        }
        .dark .tkt-closed-box {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
    </style>

    <div class="tkt-container">
        <!-- 1. Ticket Overview Card -->
        <div class="tkt-card">
            <div class="tkt-header">
                <div class="tkt-title-group">
                    <span class="tkt-number-badge">
                        {{ $ticket->ticket_number }}
                    </span>
                    <h1 class="tkt-subject">
                        {{ $ticket->subject }}
                    </h1>
                </div>

                <div class="tkt-badges-group">
                    <!-- Status Badge -->
                    <span class="tkt-badge 
                        @if($ticket->status === \App\Enums\TicketStatus::OPEN) tkt-status-open
                        @elseif($ticket->status === \App\Enums\TicketStatus::IN_PROGRESS) tkt-status-progress
                        @elseif($ticket->status === \App\Enums\TicketStatus::WAITING_USER_REPLY) tkt-status-waiting
                        @elseif($ticket->status === \App\Enums\TicketStatus::RESOLVED) tkt-status-resolved
                        @else tkt-status-closed @endif">
                        <span class="tkt-badge-dot
                            @if($ticket->status === \App\Enums\TicketStatus::OPEN) tkt-dot-open
                            @elseif($ticket->status === \App\Enums\TicketStatus::IN_PROGRESS) tkt-dot-progress
                            @elseif($ticket->status === \App\Enums\TicketStatus::WAITING_USER_REPLY) tkt-dot-waiting
                            @elseif($ticket->status === \App\Enums\TicketStatus::RESOLVED) tkt-dot-resolved
                            @else tkt-dot-closed @endif"></span>
                        {{ $ticket->status->getLabel() }}
                    </span>

                    <!-- Priority Badge -->
                    <span class="tkt-badge
                        @if($ticket->priority === \App\Enums\TicketPriority::URGENT) tkt-priority-urgent
                        @elseif($ticket->priority === \App\Enums\TicketPriority::HIGH) tkt-priority-high
                        @elseif($ticket->priority === \App\Enums\TicketPriority::MEDIUM) tkt-priority-medium
                        @else tkt-priority-low @endif">
                        {{ $ticket->priority->getLabel() }}
                    </span>

                    <!-- Category Badge -->
                    <span class="tkt-badge tkt-cat-badge">
                        {{ $ticket->category->getLabel() }}
                    </span>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="tkt-grid">
                <div>
                    <span class="tkt-meta-label">{{ __('tickets.fields.creator') }}</span>
                    <span class="tkt-meta-value">{{ $ticket->user?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="tkt-meta-label">{{ __('tickets.fields.assigned_to') }}</span>
                    <span class="tkt-meta-value">{{ $ticket->assignedTo?->name ?? __('tickets.unknown_user') }}</span>
                </div>
                <div>
                    <span class="tkt-meta-label">{{ __('tickets.fields.created_at') }}</span>
                    <span class="tkt-meta-value">{{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div>
                    <span class="tkt-meta-label">{{ __('tickets.fields.last_reply_at') }}</span>
                    <span class="tkt-meta-value">{{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : '—' }}</span>
                </div>
            </div>
        </div>

        <!-- 2. Original Ticket Issue / Description -->
        <div class="tkt-card">
            <div class="tkt-author-info" style="margin-bottom: 1rem;">
                <div class="tkt-avatar tkt-avatar-creator">
                    {{ mb_substr($ticket->user?->name ?? 'U', 0, 1) }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="tkt-author-name">{{ $ticket->user?->name ?? '—' }}</span>
                        <span class="tkt-author-role-badge" style="background: #f1f5f9; color: #475569;">
                            {{ __('tickets.fields.creator') }}
                        </span>
                    </div>
                    <span class="tkt-msg-time">{{ $ticket->created_at->diffForHumans() }} ({{ $ticket->created_at->format('Y-m-d H:i') }})</span>
                </div>
            </div>

            <div class="tkt-desc-box">
                {{ $ticket->description }}
            </div>

            @if(!empty($ticket->attachments) && count($ticket->attachments) > 0)
                <div class="tkt-attachments-wrap">
                    <span style="font-size: 0.75rem; font-weight: 600; color: #64748b; width: 100%; margin-bottom: 0.25rem;">{{ __('tickets.fields.attachments') }}:</span>
                    @foreach($ticket->attachments as $attachment)
                        <a href="{{ Storage::url($attachment) }}" target="_blank" class="tkt-attach-link">
                            <x-filament::icon icon="heroicon-o-paper-clip" style="width: 14px; height: 14px;" />
                            <span>{{ basename($attachment) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 3. Conversation Messages Timeline -->
        @if($ticket->messages->count() > 0)
            <div>
                <h3 class="tkt-thread-title">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" style="width: 22px; height: 22px; color: #0284c7;" />
                    <span>{{ __('tickets.sections.conversation') }} ({{ $ticket->messages->count() }})</span>
                </h3>

                @foreach($ticket->messages as $msg)
                    @php
                        $isNote = $msg->is_internal_note;
                        $isStaff = $msg->user_id !== $ticket->user_id;
                        
                        // Hide internal notes from non-managers
                        if ($isNote && !$isManager) {
                            continue;
                        }
                    @endphp

                    <div class="tkt-msg-card @if($isNote) tkt-msg-note @elseif($isStaff) tkt-msg-staff @endif">
                        <div class="tkt-msg-header">
                            <div class="tkt-author-info">
                                <div class="tkt-avatar @if($isNote) tkt-avatar-note @elseif($isStaff) tkt-avatar-staff @else tkt-avatar-creator @endif">
                                    {{ mb_substr($msg->user?->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span class="tkt-author-name">{{ $msg->user?->name ?? '—' }}</span>
                                        @if($isNote)
                                            <span class="tkt-author-role-badge" style="background: #fef3c7; color: #92400e; font-weight: 700;">
                                                🔒 {{ __('tickets.internal_note') }}
                                            </span>
                                        @elseif($isStaff)
                                            <span class="tkt-author-role-badge" style="background: #dbeafe; color: #1e40af; font-weight: 600;">
                                                {{ __('tickets.staff_member') }}
                                            </span>
                                        @else
                                            <span class="tkt-author-role-badge" style="background: #f1f5f9; color: #475569;">
                                                {{ __('tickets.fields.creator') }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="tkt-msg-time">{{ $msg->created_at->diffForHumans() }} ({{ $msg->created_at->format('Y-m-d H:i') }})</span>
                                </div>
                            </div>
                        </div>

                        <div class="tkt-msg-body">
                            {{ $msg->message }}
                        </div>

                        @if(!empty($msg->attachments) && count($msg->attachments) > 0)
                            <div class="tkt-attachments-wrap" style="padding-right: 3.25rem;">
                                @foreach($msg->attachments as $attachment)
                                    <a href="{{ Storage::url($attachment) }}" target="_blank" class="tkt-attach-link">
                                        <x-filament::icon icon="heroicon-o-paper-clip" style="width: 14px; height: 14px;" />
                                        <span>{{ basename($attachment) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- 4. Quick Reply Box -->
        @if($ticket->status === \App\Enums\TicketStatus::CLOSED)
            <div class="tkt-closed-box">
                <div style="margin-bottom: 0.5rem;">
                    <x-filament::icon icon="heroicon-o-lock-closed" style="width: 36px; height: 36px; margin: 0 auto; color: #94a3b8;" />
                </div>
                <h4 style="font-weight: 700; font-size: 1.1rem; color: #334155; margin-bottom: 0.25rem;">{{ __('tickets.statuses.closed') }}</h4>
                <p style="font-size: 0.85rem; margin: 0;">هذه التذكرة مغلقة حالياً. لإضافة ردود جديدة، يمكنك إعادة فتح التذكرة من زر الإجراءات بالأعلى.</p>
            </div>
        @else
            <div class="tkt-reply-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <x-filament::icon icon="heroicon-o-paper-airplane" style="width: 20px; height: 20px; color: #0284c7; transform: rotate(180deg);" />
                        <span>{{ __('tickets.sections.add_reply') }}</span>
                    </h3>

                    @if($isManager)
                        <label class="tkt-internal-checkbox">
                            <input type="checkbox" wire:model="isInternalNote" style="border-radius: 4px;">
                            <span>{{ __('tickets.fields.is_internal_note') }}</span>
                        </label>
                    @endif
                </div>

                <div>
                    <textarea 
                        wire:model="replyMessage" 
                        rows="4" 
                        placeholder="{{ __('tickets.fields.reply_message') }}..."
                        class="tkt-reply-textarea"
                        required></textarea>

                    @error('replyMessage')
                        <span style="font-size: 0.8rem; color: #ef4444; display: block; margin-top: 0.35rem;">{{ $message }}</span>
                    @enderror

                    <div class="tkt-reply-footer">
                        <!-- Status selector -->
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.825rem; color: #64748b;">{{ __('tickets.fields.update_status_on_reply') }}:</span>
                            <select wire:model="newStatus" class="tkt-status-select">
                                @foreach(\App\Enums\TicketStatus::cases() as $st)
                                    <option value="{{ $st->value }}">{{ $st->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Send Button -->
                        <button 
                            type="button" 
                            wire:click="postReply" 
                            wire:loading.attr="disabled"
                            class="tkt-submit-btn">
                            <span wire:loading.remove wire:target="postReply" style="display: inline-flex; align-items: center;">
                                <x-filament::icon icon="heroicon-m-paper-airplane" style="width: 16px; height: 16px; transform: rotate(180deg);" />
                            </span>
                            <span wire:loading wire:target="postReply" style="display: inline-flex; align-items: center;">
                                <x-filament::icon icon="heroicon-m-arrow-path" class="animate-spin" style="width: 16px; height: 16px;" />
                            </span>
                            <span>{{ __('tickets.actions.reply') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
