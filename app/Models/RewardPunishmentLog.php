<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RewardPunishmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'student_id',
        'rules_id',
        'date',
        'given_by',
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
}
