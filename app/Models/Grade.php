<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'homeroom_teacher_id',
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
     * Get the homeroom teacher for the grade.
     */
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    /**
     * Get the students for the grade.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
