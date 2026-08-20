<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateFile extends Model
{
    use SoftDeletes;

    protected $fillable = ['disk', 'path', 'original_name', 'mime_type', 'size', 'owner_type', 'owner_id', 'uploaded_by', 'metadata'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
