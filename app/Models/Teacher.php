<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'fullname',
        'phone_number',
        'address',
        'subject',
        'hire_date',
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
     * Get the user that owns the teacher.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the grade where this teacher is homeroom.
     */
    public function homeroomGrade()
    {
        return $this->hasOne(Grade::class, 'homeroom_teacher_id');
    }
}
