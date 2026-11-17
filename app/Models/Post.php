<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['user_id', 'body', 'image', 'mentions', 'pinned_at', 'edited_at'];

    protected $casts = ['mentions' => 'array', 'pinned_at' => 'datetime', 'edited_at' => 'datetime'];

    public function author()    { return $this->belongsTo(User::class, 'user_id'); }
    public function reactions() { return $this->hasMany(PostReaction::class); }
    public function comments()  { return $this->hasMany(PostComment::class); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}