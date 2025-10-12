<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'fullname',
        'grade_id',
        'birth_date',
        'address',
        'phone_number',
        'image',
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
     * Get the user that owns the student.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the grade that owns the student.
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the targets for the student.
     */
    public function targets()
    {
        return $this->hasMany(Target::class);
    }

    /**
     * Get the student point for the student.
     */
    public function studentPoint()
    {
        return $this->hasOne(StudentPoint::class);
    }
}
