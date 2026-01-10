<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueType extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = ["id"];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function poly(): BelongsTo
    {
        return $this->belongsTo(Poly::class);
    }

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
