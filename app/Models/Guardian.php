<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'full_name', 'identity_number', 'phone', 'alternative_phone', 'email',
        'identity_private_file_id', 'notes', 'created_by', 'updated_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identityDocument(): BelongsTo
    {
        return $this->belongsTo(PrivateFile::class, 'identity_private_file_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot(['relationship', 'is_primary', 'can_receive_notifications'])->withTimestamps();
    }
}
