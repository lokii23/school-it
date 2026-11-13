<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Support\Roster;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $announcements = Announcement::latest()->take(6)->get();

        // The sections where this teacher teaches a subject
        $mine = $user->role === 'teacher'
            ? SubjectAssignment::where('teacher_id', $user->id)->pluck('section_id')->unique()->values()
            : collect();

        $stats = match ($user->role) {
            'admin' => [
                'Users'           => User::count(),
                'Exams & Quizzes' => Exam::count(),
                'Announcements'   => Announcement::count(),
            ],
            'teacher' => [
                'Sections' => $mine->count(),
                'Students' => Roster::forTeacher($user)->count(),
                'My Exams' => Exam::managedBy($user)->count(),
            ],
            default => [
                'Available Exams' => Exam::forStudent($user)
                    ->openNow()
                    ->whereDoesntHave('results', fn ($q) => $q->where('user_id', $user->id))
                    ->count(),
                'Results'         => ExamResult::where('user_id', $user->id)->count(),
                'Average Grade'   => ($g = Grade::generalAverage($user->id)) !== null ? number_format($g, 1) : '—',
            ],
        };

        $panel = match ($user->role) {
            'admin' => [
                'type' => 'users', 'title' => 'Recently added users',
                'items' => User::latest()->take(5)->get(),
            ],
            'teacher' => [
                'type' => 'results', 'title' => 'Latest exam results',
                'items' => ExamResult::with(['student', 'exam'])
                    ->whereHas('exam', fn ($q) => $q->managedBy($user))
                    ->latest()->take(5)->get(),
            ],
            default => [
                'type' => 'exams', 'title' => 'Available exams',
                'items' => Exam::withCount('questions')
                    ->forStudent($user)
                    ->openNow()
                    ->whereDoesntHave('results', fn ($q) => $q->where('user_id', $user->id))
                    ->latest()->take(5)->get(),
            ],
        };

        return view('dashboard', compact('announcements', 'stats', 'panel'));
    }
}