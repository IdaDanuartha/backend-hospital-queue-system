<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poly extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $guarded = ["id"];

    protected $table = 'polys';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function queueTypes(): HasMany
    {
        return $this->hasMany(QueueType::class);
    }

    public function serviceHours(): HasMany
    {
        return $this->hasMany(PolyServiceHour::class);
    }

    // Scope
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
