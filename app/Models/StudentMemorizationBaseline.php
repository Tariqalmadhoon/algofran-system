<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMemorizationBaseline extends Model
{
    protected $fillable = ['student_id', 'start_ayah_id', 'end_ayah_id', 'recorded_at', 'recorded_by', 'notes'];

    protected function casts(): array
    {
        return ['recorded_at' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function startAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'start_ayah_id');
    }

    public function endAyah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'end_ayah_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
