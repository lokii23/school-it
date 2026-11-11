<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ---------- My Profile (all roles) ----------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfilePhotoController::class, 'update'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');

    // ---------- Admin only ----------
    Route::middleware('role:admin')->group(function () {
        Route::resource('announcements', AnnouncementController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('grades/lock', [GradeController::class, 'lock'])->name('grades.lock');
    });

    // ---------- Admin + Teacher ----------
    Route::middleware('role:admin,teacher')->group(function () {

        // Sections
        Route::patch('sections/students/{user}', [SectionController::class, 'assign'])->name('sections.assign');
        Route::resource('sections', SectionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Exams
        Route::resource('exams', ExamController::class)->except('show');

        // Results
        Route::get('results', [ResultController::class, 'index'])->name('results.index');
        Route::get('results/all', [ResultController::class, 'all'])->name('results.all');
        Route::get('results/export', [ResultController::class, 'export'])->name('results.export');
        Route::get('results/exam/{exam}', [ResultController::class, 'exam'])->whereNumber('exam')->name('results.exam');
        Route::get('results/exam/{exam}/export', [ResultController::class, 'examExport'])->whereNumber('exam')->name('results.exam.export');
        Route::get('results/{result}', [ResultController::class, 'show'])->whereNumber('result')->name('results.show');
        Route::delete('results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');

        // Grades (grade sheet, exports and printouts)
        Route::prefix('grades')->name('grades.')->group(function () {
            Route::get('/', [GradeController::class, 'index'])->name('index');
            Route::post('/sheet', [GradeController::class, 'save'])->name('save');
            Route::get('/export', [GradeController::class, 'export'])->name('export');
            Route::get('/print', [GradeController::class, 'printSheet'])->name('print');
            Route::get('/report-cards', [GradeController::class, 'reportCards'])->name('reports');
            Route::get('/report/{student}', [GradeController::class, 'report'])->whereNumber('student')->name('report');
        });
    });

    // ---------- Student only ----------
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {

        Route::get('exams', [StudentExamController::class, 'index'])->name('exams');

        Route::get('exams/{exam}/access', [StudentExamController::class, 'access'])->name('exams.access');
        Route::post('exams/{exam}/access', [StudentExamController::class, 'verify'])->name('exams.verify');

        Route::get('exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
        Route::post('exams/{exam}', [StudentExamController::class, 'submit'])->name('exams.submit');
        Route::get('exams/{exam}/result', [StudentExamController::class, 'result'])->name('exams.result');
        Route::get('exams/{exam}/review', [StudentExamController::class, 'review'])->name('exams.review');

        Route::get('results', [ResultController::class, 'mine'])->name('results');

        Route::get('grades', [GradeController::class, 'mine'])->name('grades');
        Route::get('grades/report', [GradeController::class, 'reportMine'])->name('grades.report');
    });
});

require __DIR__.'/auth.php';