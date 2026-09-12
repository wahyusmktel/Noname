<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Filterable;

    /**
     * Primary key bertipe string (UUID) non-incrementing
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Tentukan apakah model menggunakan multi-tenant scoping otomatis.
     */
    protected bool $tenantScoped = true;

    protected static function booted(): void
    {
        static::bootBaseModel();
    }

    protected static function bootBaseModel(): void
    {
        $instance = new static;

        // Pasang TenantScope jika diaktifkan pada model
        if ($instance->tenantScoped) {
            static::addGlobalScope(new TenantScope);

            // Isi tenant_id secara otomatis saat membuat record baru jika belum ada
            static::creating(function ($model) {
                if (auth()->check() && empty($model->tenant_id) && !empty(auth()->user()->tenant_id)) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });
        }
    }
}
