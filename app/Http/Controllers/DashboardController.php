<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $announcements = Announcement::latest()->take(6)->get();

        $stats = match ($user->role) {
            'admin' => [
                'Users' => User::count(),
                'Exams & Quizzes' => Exam::count(),
                'Announcements' => Announcement::count(),
            ],
            'teacher' => [
                'Sections' => Section::count(),
                'Students' => User::where('role', 'student')->count(),
                'My Exams' => Exam::where('user_id', $user->id)->count(),
            ],
            default => [
                'Available Exams' => Exam::where('section_id', $user->section_id)
                    ->openNow()
                    ->whereDoesntHave('results', fn ($q) => $q->where('user_id', $user->id))->count(),
                'Results' => ExamResult::where('user_id', $user->id)->count(),
                'Average Grade' => ($g = Grade::generalAverage($user->id)) !== null ? number_format($g, 2) : '—',
            ],
        };

        $panel = match ($user->role) {
            'admin' => ['type' => 'users', 'title' => 'Recently added users',
                'items' => User::latest()->take(5)->get()],
            'teacher' => ['type' => 'results', 'title' => 'Latest exam results',
                'items' => ExamResult::with(['student', 'exam'])->latest()->take(5)->get()],
            default => ['type' => 'exams', 'title' => 'Available exams',
                'items' => Exam::withCount('questions')
                    ->where('section_id', $user->section_id)
                    ->openNow()
                    ->whereDoesntHave('results', fn ($q) => $q->where('user_id', $user->id))
                    ->latest()->take(5)->get()],
        };

        return view('dashboard', compact('announcements', 'stats', 'panel'));
    }
}
