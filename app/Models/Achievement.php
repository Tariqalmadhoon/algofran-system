<?php

namespace App\Models;

use App\Enums\AchievementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $fillable = ['student_id', 'fingerprint', 'type', 'title', 'description', 'achieved_at', 'issuer', 'metadata', 'created_by'];

    protected function casts(): array
    {
        return ['type' => AchievementType::class, 'achieved_at' => 'date', 'metadata' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
