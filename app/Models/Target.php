<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'student_id',
        'description',
        'start_date',
        'end_date',
        'status',
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
     * Get the student that owns the target.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
