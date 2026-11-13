<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name', 'middle_name', 'last_name',
        'email',
        'password',
        'role', 
        'section_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_irregular' => 'boolean',
        ];
    }
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // Build the full name from the name parts
            if ($user->first_name || $user->last_name) {
                $user->name = collect([$user->first_name, $user->middle_name, $user->last_name])
                    ->filter()
                    ->implode(' ');
            }

            // Every student gets the next Student ID. A new account without a role is a student.
            if (($user->role ?? 'student') === 'student' && ! $user->student_number) {
                $user->student_number = static::nextStudentNumber();
            }
        });
    }

    public function section()    { return $this->belongsTo(Section::class); }
    public function results()    { return $this->hasMany(ExamResult::class); }
    public function grades()     { return $this->hasMany(Grade::class, 'student_id'); }

    // Public URL of the profile photo, or null if none
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo ? asset('storage/' . $this->photo) : null);
    }

    // Initials shown when there is no photo
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $first = $this->first_name ?: $this->name;

            return strtoupper(mb_substr($first ?? '', 0, 1) . mb_substr($this->last_name ?? '', 0, 1));
        });
    }

    public function homeRoute(): string
    {
        $home = [
            'admin'   => 'dashboard',   // e.g. 'announcements.index'
            'teacher' => 'dashboard',   // e.g. 'exams.index'
            'student' => 'dashboard',   // e.g. 'student.exams'
        ][$this->role] ?? 'dashboard';

        return \Illuminate\Support\Facades\Route::has($home) ? $home : 'dashboard';
    }

    public function getListNameAttribute(): string
    {
        if (! $this->last_name) {
            return $this->name;
        }

        $mi = $this->middle_name ? ' ' . mb_substr($this->middle_name, 0, 1) . '.' : '';

        return $this->last_name . ', ' . $this->first_name . $mi;
    }

    public const STUDENT_NUMBER_START = 2026001;

    public static function nextStudentNumber(): int
    {
        $max = (int) static::max('student_number');

        return max($max + 1, self::STUDENT_NUMBER_START);
    }

    public function assignments() { return $this->hasMany(SubjectAssignment::class, 'teacher_id'); }
    public function enrollments() { return $this->hasMany(StudentEnrollment::class, 'student_id'); }
}
