<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Terapkan scope untuk membatasi query hanya pada tenant milik user yang sedang aktif.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Jika bukan superadmin, isolasi data berdasarkan tenant_id
            if (!method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
                if (!empty($user->tenant_id)) {
                    $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
                }
            }
        }
    }
}
