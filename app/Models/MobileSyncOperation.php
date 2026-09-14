<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileSyncOperation extends Model
{
    protected $fillable = [
        'mobile_device_id', 'user_id', 'operation_uuid', 'operation_type', 'payload_hash',
        'status', 'daily_record_id', 'response', 'error_code', 'error_message',
        'client_created_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'client_created_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class, 'mobile_device_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }
}
