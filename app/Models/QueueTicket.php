<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueTicket extends BaseModel
{
    use HasFactory;

    protected $guarded = ["id"];

    protected function casts(): array
    {
        return [
            'status' => QueueStatus::class,
            'service_date' => 'date',
            'issued_at' => 'datetime',
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'finished_at' => 'datetime',
            'is_priority' => 'boolean',
        ];
    }

    public function queueType(): BelongsTo
    {
        return $this->belongsTo(QueueType::class);
    }

    public function handledByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'handled_by_staff_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QueueEvent::class);
    }

    public function publicTokens(): HasMany
    {
        return $this->hasMany(PublicQueueToken::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('service_date', today());
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', QueueStatus::WAITING);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Helpers
    public function getWaitingTimeMinutes()
    {
        if (!$this->served_at) {
            return now()->diffInMinutes($this->issued_at);
        }
        return $this->served_at->diffInMinutes($this->issued_at);
    }

    public function getServiceTimeMinutes()
    {
        if ($this->served_at && $this->finished_at) {
            return $this->finished_at->diffInMinutes($this->served_at);
        }
        return null;
    }
}
