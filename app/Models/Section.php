<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name', 'year_level'];

    public function students() { return $this->hasMany(User::class)->where('role', 'student')->where('is_irregular', false); }
    public function exams()    { return $this->hasMany(Exam::class); }
    
}
