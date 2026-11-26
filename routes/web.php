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
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IrregularController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FeedNotificationController;

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

    // ---------- News Feed (all roles) ----------
    Route::get('feed', [FeedController::class, 'index'])->name('feed.index');
    Route::get('feed/{post}/reactions', [FeedController::class, 'reactions'])->whereNumber('post')->name('feed.reactions');
    Route::get('feed/{post}/comments', [FeedController::class, 'comments'])->whereNumber('post')->name('feed.comments');
    

    Route::post('feed', [FeedController::class, 'store'])->middleware('throttle:10,1')->name('feed.store');
    Route::get('feed/people', [FeedController::class, 'people'])->middleware('throttle:90,1')->name('feed.people');

    Route::get('feed/live', [FeedController::class, 'live'])->middleware('throttle:120,1')->name('feed.live');
    Route::get('feed/rail', [FeedController::class, 'rail'])->middleware('throttle:60,1')->name('feed.rail');


    // Notifications
    Route::get('notifications', [FeedNotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/count', [FeedNotificationController::class, 'count'])->name('notifications.count');
    Route::post('notifications/read-all', [FeedNotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{notification}/read', [FeedNotificationController::class, 'read'])->whereNumber('notification')->name('notifications.read');
    Route::get('notifications/poll', [FeedNotificationController::class, 'poll'])
    ->middleware('throttle:240,1')->name('notifications.poll');

    Route::middleware('throttle:120,1')->group(function () {
        Route::patch('feed/{post}', [FeedController::class, 'update'])->whereNumber('post')->name('feed.update');
        Route::delete('feed/{post}', [FeedController::class, 'destroy'])->whereNumber('post')->name('feed.destroy');
        Route::post('feed/{post}/react', [FeedController::class, 'react'])->whereNumber('post')->name('feed.react');
        Route::post('feed/{post}/comments', [FeedController::class, 'commentStore'])->whereNumber('post')->name('feed.comments.store');
        Route::delete('feed/comments/{comment}', [FeedController::class, 'commentDestroy'])->whereNumber('comment')->name('feed.comments.destroy');
        
    });

    Route::post('feed/{post}/pin', [FeedController::class, 'pin'])->whereNumber('post')->middleware('role:admin')->name('feed.pin');

    // ---------- Admin only ----------
    Route::middleware('role:admin')->group(function () {
        Route::resource('announcements', AnnouncementController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Subjects and teacher assignments
        Route::resource('subjects', SubjectController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('subjects/{subject}/assign', [SubjectController::class, 'assign'])->name('subjects.assign');
        Route::delete('subject-assignments/{assignment}', [SubjectController::class, 'unassign'])->name('subjects.unassign');

        // Sections: only the admin creates, renames, deletes and places students
        Route::patch('sections/students/{user}', [SectionController::class, 'assign'])->name('sections.assign');
        Route::resource('sections', SectionController::class)->only(['store', 'update', 'destroy']);

        // Finalize / unlock grades
        Route::post('grades/lock', [GradeController::class, 'lock'])->name('grades.lock');

        // Irregular students
        Route::get('irregular', [IrregularController::class, 'index'])->name('irregular.index');
        Route::post('irregular', [IrregularController::class, 'store'])->name('irregular.store');
        Route::post('irregular/{student}/subjects', [IrregularController::class, 'enroll'])->name('irregular.enroll');
        Route::post('irregular/{student}/regular', [IrregularController::class, 'regular'])->name('irregular.regular');
    });

    // ---------- Admin + Teacher ----------
    Route::middleware('role:admin,teacher')->group(function () {

        // Sections (teachers see only their own, view only)
        Route::get('sections', [SectionController::class, 'index'])->name('sections.index');

        // Exams
        Route::post('exams/{exam}/duplicate', [ExamController::class, 'duplicate'])->whereNumber('exam')->name('exams.duplicate');
        Route::resource('exams', ExamController::class)->except('show');
        

        // Results
        Route::get('results', [ResultController::class, 'index'])->name('results.index');
        Route::get('results/all', [ResultController::class, 'all'])->name('results.all');
        Route::get('results/export', [ResultController::class, 'export'])->name('results.export');
        Route::get('results/exam/{exam}', [ResultController::class, 'exam'])->whereNumber('exam')->name('results.exam');
        Route::get('results/exam/{exam}/export', [ResultController::class, 'examExport'])->whereNumber('exam')->name('results.exam.export');
        Route::get('results/{result}', [ResultController::class, 'show'])->whereNumber('result')->name('results.show');
        Route::delete('results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
        Route::patch('results/{result}/score', [ResultController::class, 'score'])->whereNumber('result')->name('results.score');

        // Grades (grade table, exports and printouts)
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