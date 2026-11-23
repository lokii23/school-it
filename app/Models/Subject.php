<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'year_level'];

    public function assignments() { return $this->hasMany(SubjectAssignment::class); }
}