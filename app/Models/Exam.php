<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'user_id', 'section_id', 'title', 'subject', 'type',
        'passkey', 'duration_minutes', 'opens_at', 'closes_at',
        'review_mode', 'score_mode',
    ];


    protected $casts = [
        'passkey'   => 'encrypted',
        'opens_at'  => 'datetime',
        'closes_at' => 'datetime',
    ];

    // Exams students can start right now
    public function scopeOpenNow($query)
    {
        return $query
            ->where(fn ($w) => $w->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('closes_at')->orWhere('closes_at', '>=', now()));
    }

    // 'upcoming' | 'open' | 'closed'
    public function getStatusAttribute(): string
    {
        if ($this->opens_at && now()->lt($this->opens_at)) return 'upcoming';
        if ($this->closes_at && now()->gt($this->closes_at)) return 'closed';

        return 'open';
    }   

    // May students see their score yet?
    public function scoreVisible(): bool
    {
        if ($this->score_mode !== 'after_close') {
            return true;
        }

        return $this->closes_at ? now()->gt($this->closes_at) : true;
    }

    // May students see the correct answers yet?
    public function reviewOpen(): bool
    {
        if ($this->review_mode === 'off') {
            return false;
        }

        // Answers are never revealed before the score is
        if (! $this->scoreVisible()) {
            return false;
        }

        return match ($this->review_mode) {
            'after_submit' => true,
            default        => $this->closes_at ? now()->gt($this->closes_at) : true,
        };
    }
    
    public function teacher()   { return $this->belongsTo(User::class, 'user_id'); }
    public function section()   { return $this->belongsTo(Section::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function results()   { return $this->hasMany(ExamResult::class); }

}
