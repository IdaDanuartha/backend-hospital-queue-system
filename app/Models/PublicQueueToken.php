<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicQueueToken extends BaseModel
{
    protected $guarded = ["id"];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }

    public function isExpired()
    {
        if (!$this->expires_at) return false;
        return now()->greaterThan($this->expires_at);
    }

    public static function generate($queueTicketId, $expiresInDays = 7)
    {
        return static::create([
            'queue_ticket_id' => $queueTicketId,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays($expiresInDays),
            'created_at' => now(),
        ]);
    }
}
