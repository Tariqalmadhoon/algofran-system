<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    protected $fillable = ['uuid', 'user_id', 'report_type', 'format', 'filters', 'status', 'private_file_id', 'rows_count', 'failure_message', 'completed_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function privateFile(): BelongsTo
    {
        return $this->belongsTo(PrivateFile::class);
    }
}
