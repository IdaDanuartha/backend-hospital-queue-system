<?php

namespace App\Models;

use App\Enums\QueueAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueEvent extends BaseModel
{
    protected $guarded = ["id"];
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'action' => QueueAction::class,
            'event_time' => 'datetime',
        ];
    }

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
