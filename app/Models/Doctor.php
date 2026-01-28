<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = ["id"];

    public function poly(): BelongsTo
    {
        return $this->belongsTo(Poly::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'assigned_doctor_id');
    }
}
