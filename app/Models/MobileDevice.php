<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobileDevice extends Model
{
    protected $fillable = [
        'user_id', 'uuid', 'name', 'platform', 'app_version',
        'last_seen_at', 'last_synced_at', 'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function syncOperations(): HasMany
    {
        return $this->hasMany(MobileSyncOperation::class);
    }
}
