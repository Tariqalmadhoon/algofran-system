<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'avatar_private_file_id',
        'active',
        'locale',
        'archived_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'archived_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function requiresTwoFactorAuthentication(): bool
    {
        return (bool) config('system.identity.two_factor_enabled', false)
            && $this->hasAnyRole(config('system.identity.two_factor_required_roles', []));
    }

    /**
     * Determine whether this user must be limited to their own teaching assignments.
     *
     * A center manager may also be an active teacher. In that case the user keeps
     * the wider administrative access while the teacher screens themselves remain
     * scoped to the Halaqas assigned to their teacher profile.
     */
    public function requiresTeacherAssignmentScope(): bool
    {
        return $this->hasRole('teacher')
            && ! $this->hasAnyRole(['super-admin', 'center-manager', 'academic-supervisor', 'registrar']);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(PrivateFile::class, 'avatar_private_file_id');
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function guardianProfile(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }
}
