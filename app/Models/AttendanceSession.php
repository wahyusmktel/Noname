<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AttendanceSession extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'tentor_id',
        'study_group_id',
        'academic_year_id',
        'date',
        'subject_name',
        'topic_description',
        'documentation_photo',
        'created_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected $appends = [
        'photo_url',
    ];

    /**
     * URL Foto dokumentasi tersimpan atau null
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->documentation_photo && Storage::disk('public')->exists($this->documentation_photo)) {
                    return Storage::disk('public')->url($this->documentation_photo);
                }
                return null;
            }
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
