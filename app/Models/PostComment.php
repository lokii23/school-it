<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    protected $fillable = ['post_id', 'user_id', 'body', 'mentions'];
    protected $casts = ['mentions' => 'array'];
    
    public function post()   { return $this->belongsTo(Post::class); }
    public function author() { return $this->belongsTo(User::class, 'user_id'); }
}