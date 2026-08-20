<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    protected $fillable = ['student_id', 'halaqa_id', 'record_date', 'status', 'recorded_by', 'notes'];

    protected function casts(): array
    {
        return ['record_date' => 'date', 'status' => AttendanceStatus::class];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function dailyRecord(): HasOne
    {
        return $this->hasOne(DailyRecord::class);
    }
}
