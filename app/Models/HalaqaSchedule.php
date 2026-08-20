<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalaqaSchedule extends Model
{
    protected $fillable = ['halaqa_id', 'weekday', 'starts_at', 'ends_at', 'room'];

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }
}
