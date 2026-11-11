<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = ['user_id', 'exam_id', 'score', 'total', 'answers'];

    protected $casts = ['answers' => 'array'];

    public function student() { return $this->belongsTo(User::class, 'user_id'); }
    public function exam()    { return $this->belongsTo(Exam::class); }
}
