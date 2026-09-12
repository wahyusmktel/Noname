<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdminBimbel(): bool
    {
        return $this->role === 'admin_bimbel';
    }

    public function isTutor(): bool
    {
        return $this->role === 'tutor';
    }

    public function isStudent(): bool
    {
        return $this->role === 'siswa';
    }

    public function isParent(): bool
    {
        return $this->role === 'orang_tua';
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function tentor(): HasOne
    {
        return $this->hasOne(Tentor::class);
    }
}
