<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'year_level', 'term_id'];

    public function term()        { return $this->belongsTo(Term::class); }
    public function assignments() { return $this->hasMany(SubjectAssignment::class); }
}