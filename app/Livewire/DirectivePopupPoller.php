<?php

namespace App\Livewire;

use App\Models\Directives\DirectiveRecipient;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DirectivePopupPoller extends Component
{
    public ?int $recipientId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $directiveData = null;

    public int $pendingCount = 0;

    public function mount(): void
    {
        $this->loadPendingDirective();
    }

    public function loadPendingDirective(): void
    {
        $userId = Auth::id();
        if (! $userId) {
            $this->resetPopup();

            return;
        }

        $baseQuery = DirectiveRecipient::query()
            ->with(['directive.category', 'directive.creator:id,name'])
            ->where('user_id', $userId)
            ->whereNull('acknowledged_at')
            ->whereHas('directive', fn ($q) => $q->where('is_active', true))
            ->orderBy('id');

        $next = (clone $baseQuery)->first();

        if (! $next || ! $next->directive) {
            $this->resetPopup();

            return;
        }

        $count = $baseQuery->count();

        $directive = $next->directive;

        $this->recipientId = $next->id;
        $this->pendingCount = $count;
        $this->directiveData = [
            'title' => $directive->title,
            'description' => $directive->description,
            'category_name' => $directive->category?->localizedName() ?? '',
            'category_color' => $directive->category?->color ?: 'info',
            'sender' => $directive->creator?->name ?? '',
            'sent_at' => $directive->created_at?->format('Y-m-d H:i') ?? '',
        ];
    }

    public function acknowledge(): void
    {
        $userId = Auth::id();
        if (! $userId || ! $this->recipientId) {
            return;
        }

        $recipient = DirectiveRecipient::query()
            ->where('id', $this->recipientId)
            ->where('user_id', $userId)
            ->whereNull('acknowledged_at')
            ->first();

        if ($recipient) {
            $recipient->update([
                'acknowledged_at' => now(),
            ]);

            FilamentNotification::make()
                ->title(__('directives.popup.acknowledged_toast'))
                ->success()
                ->duration(4000)
                ->send();
        }

        $this->loadPendingDirective();
    }

    protected function resetPopup(): void
    {
        $this->recipientId = null;
        $this->directiveData = null;
        $this->pendingCount = 0;
    }

    public function render()
    {
        return view('livewire.directive-popup-poller');
    }
}
