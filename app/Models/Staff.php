<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = ["id"];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function poly(): BelongsTo
    {
        return $this->belongsTo(Poly::class);
    }

    public function queueEvents(): HasMany
    {
        return $this->hasMany(QueueEvent::class);
    }

    public function handledTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'handled_by_staff_id');
    }
}
