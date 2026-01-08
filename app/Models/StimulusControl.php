<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StimulusControl extends Model
{
    protected $fillable = [
        'uuid',
        'student_id',
        'value',
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

    protected $casts = [
        'value' => 'string',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
