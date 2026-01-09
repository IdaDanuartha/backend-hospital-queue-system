<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolyServiceHour extends BaseModel
{
    protected $guarded = ["id"];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'is_active' => 'boolean',
            'open_time' => 'datetime:H:i',
            'close_time' => 'datetime:H:i',
        ];
    }
    
    public function poly(): BelongsTo
    {
        return $this->belongsTo(Poly::class);
    }
}
