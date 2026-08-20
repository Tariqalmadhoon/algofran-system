<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['center_id', 'name', 'code', 'phone', 'address', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function halaqas(): HasMany
    {
        return $this->hasMany(Halaqa::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
