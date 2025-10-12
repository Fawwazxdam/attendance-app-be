<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RewardPunishmentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'student_id',
        'teacher_id',
        'rule_id',
        'type',
        'description',
        'status',
        'given_date',
        'notes',
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
     * Get the student that owns the reward punishment record.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the teacher that owns the reward punishment record.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the rule that owns the reward punishment record.
     */
    public function rule()
    {
        return $this->belongsTo(RewardPunishmentRule::class, 'rule_id');
    }
}
