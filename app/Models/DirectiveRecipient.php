<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectiveRecipient extends Model
{
    protected $fillable = [
        'directive_id',
        'user_id',
        'employee_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'directive_id' => 'integer',
            'user_id' => 'integer',
            'employee_id' => 'integer',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function directive(): BelongsTo
    {
        return $this->belongsTo(Directive::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
