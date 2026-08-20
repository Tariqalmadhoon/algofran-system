<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class CmsMedia extends Model
{
    use SoftDeletes;

    protected $table = 'cms_media';

    protected $fillable = ['disk', 'path', 'original_name', 'mime_type', 'size', 'kind', 'title', 'alt_text', 'caption', 'is_gallery', 'sort_order', 'uploaded_by'];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return ['is_gallery' => 'boolean'];
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function featuredContents(): HasMany
    {
        return $this->hasMany(CmsContent::class, 'featured_media_id');
    }
}
