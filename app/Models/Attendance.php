<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'student_id',
        'date',
        'status',
        'remarks',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the student that owns the attendance.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the attendance journals for the attendance.
     */
    public function attendanceJournals()
    {
        return $this->hasMany(AttendanceJournal::class);
    }

    /**
     * Get the medias for the attendance.
     */
    public function medias()
    {
        return $this->morphMany(Media::class, 'morphable');
    }
}
