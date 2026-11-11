<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $fillable = ['user_id', 'exam_id', 'started_at'];

    protected $casts = ['started_at' => 'datetime'];

    public function exam() { return $this->belongsTo(Exam::class); }
}