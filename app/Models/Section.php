<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name'];

    public function students() { return $this->hasMany(User::class)->where('role', 'student'); }
    public function exams()    { return $this->hasMany(Exam::class); }
}
