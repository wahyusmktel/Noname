<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Student extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'study_group_id',
        'user_id',
        'username',
        'plain_password',
        'name',
        'parent_phone',
        'student_phone',
        'photo',
        'status',
    ];

    protected $appends = [
        'photo_url',
    ];

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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
