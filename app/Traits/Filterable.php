<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    /**
     * Scope query to apply dynamic search, sorting and per_page pagination.
     */
    public function scopeApplyFilters(Builder $query, Request $request, array $searchableColumns = []): Builder
    {
        // 1. Pencarian dinamis
        if ($search = $request->query('search')) {
            $query->where(function (Builder $q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $index => $column) {
                    if ($index === 0) {
                        $q->where($column, 'LIKE', "%{$search}%");
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        // 2. Sorting terfilter aman
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        
        // Hanya izinkan kolom yang valid untuk sort guna mencegah SQL Injection
        $allowedSorts = array_merge(['id', 'created_at', 'updated_at'], $searchableColumns);
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
