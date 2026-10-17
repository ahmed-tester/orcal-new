<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function types(): HasMany
    {
        return $this->hasMany(ViolationType::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
