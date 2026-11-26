<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = [
        'user_id', 'exam_id', 'score', 'total', 'answers',
        'auto_score', 'adjusted_at', 'adjusted_by',
    ];

    protected $casts = ['answers' => 'array', 'adjusted_at' => 'datetime'];

    public function student()  { return $this->belongsTo(User::class, 'user_id'); }
    public function exam()     { return $this->belongsTo(Exam::class); }
    public function adjuster() { return $this->belongsTo(User::class, 'adjusted_by'); }
}