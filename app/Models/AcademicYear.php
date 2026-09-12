<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
