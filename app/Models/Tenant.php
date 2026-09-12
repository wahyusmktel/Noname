<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'email',
        'website',
        'phone',
        'whatsapp_sender',
        'address',
        'city',
        'province',
        'postal_code',
        'operating_hours',
        'brand_color',
        'logo',
        'package_type',
        'status',
    ];

    protected $appends = [
        'logo_url',
    ];

    /**
     * URL Logo Lembaga Bimbel
     */
    protected function logoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if ($this->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo)) {
                    return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo);
                }
                return '/images/logo_bnn.png';
            }
        );
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
