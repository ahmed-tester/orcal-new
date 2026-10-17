<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'last_reply_at',
            'resolved_at',
            'closed_at',
        ];
    }


    protected $fillable = [
        'ticket_number',
        'user_id',
        'company_id',
        'assigned_to',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'attachments',
        'last_reply_at',
        'resolved_at',
        'closed_at',
        'metadata',
    ];

    protected $casts = [
        'category' => TicketCategory::class,
        'priority' => TicketPriority::class,
        'status' => TicketStatus::class,
        'attachments' => 'array',
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $year = date('Y');
                $latest = static::whereYear('created_at', $year)->latest('id')->first();
                $seq = $latest ? ((int) substr($latest->ticket_number, -4)) + 1 : 1;
                $ticket->ticket_number = sprintf('TCK-%s-%04d', $year, $seq);
            }

            if (empty($ticket->company_id) && auth()->check()) {
                $ticket->company_id = auth()->user()->company_id;
            }

            if (empty($ticket->last_reply_at)) {
                $ticket->last_reply_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }

    /**
     * Scope query based on user's permissions:
     * - Super admin / Users with 'manage_tickets' or 'view_any_ticket' can see all tickets.
     * - Regular users only see their own tickets.
     */
    public function scopeAccessibleBy(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdminOrOwner() || $user->can('manage_tickets')) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Helper to check if user can manage this ticket.
     */
    public function canBeManagedBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;

        return $user->isSuperAdminOrOwner() || $user->can('manage_tickets');
    }
}
