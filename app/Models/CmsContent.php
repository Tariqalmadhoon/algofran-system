<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsContent extends Model
{
    use SoftDeletes;

    protected $table = 'cms_contents';

    protected $fillable = ['type', 'slug', 'title', 'excerpt', 'body', 'status', 'published_at', 'featured', 'featured_media_id', 'meta_title', 'meta_description', 'sort_order', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'featured' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where(fn (Builder $dates) => $dates->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(CmsMedia::class, 'featured_media_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
