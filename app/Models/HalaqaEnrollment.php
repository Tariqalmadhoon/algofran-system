<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalaqaEnrollment extends Model
{
    protected $fillable = ['student_id', 'halaqa_id', 'starts_at', 'ends_at', 'reason', 'enrolled_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function enrollee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }
}
