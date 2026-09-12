<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Tentor extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'title_prefix',
        'name',
        'title_suffix',
        'phone',
        'email',
        'photo',
        'specialization',
        'status',
        'user_id',
        'username',
        'plain_password',
    ];

    protected $appends = [
        'full_name',
        'photo_url',
    ];

    /**
     * Nama lengkap dengan gelar depan dan gelar belakang
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $prefix = $this->title_prefix ? trim($this->title_prefix) . ' ' : '';
                $suffix = $this->title_suffix ? ', ' . trim($this->title_suffix) . '' : '';
                return $prefix . $this->name . $suffix;
            }
        );
    }

    /**
     * URL Foto profil tersimpan atau null
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->photo && Storage::disk('public')->exists($this->photo)) {
                    return Storage::disk('public')->url($this->photo);
                }
                return null;
            }
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
