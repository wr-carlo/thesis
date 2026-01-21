<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ProfessorSubjectController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Student\AssessmentController as StudentAssessmentController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Instructor\LessonController;
use App\Http\Controllers\Instructor\NotificationController as InstructorNotificationController;
use App\Http\Controllers\Instructor\ProfileController as InstructorProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/login');


// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Students
    Route::resource('students', StudentController::class)->except(['show']);
    Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset-password');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('students/template/download', [StudentController::class, 'downloadTemplate'])->name('students.template');

    // Instructors
    Route::resource('instructors', InstructorController::class)->except(['show']);
    Route::post('instructors/{instructor}/reset-password', [InstructorController::class, 'resetPassword'])->name('instructors.reset-password');
    Route::post('instructors/import', [InstructorController::class, 'import'])->name('instructors.import');
    Route::get('instructors/template/download', [InstructorController::class, 'downloadTemplate'])->name('instructors.template');

    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('sections', SectionController::class)->except(['show']);
    Route::resource('subjects', SubjectController::class)->except(['show']);

    Route::get('assignments', [ProfessorSubjectController::class, 'index'])->name('assignments.index');
    Route::post('assignments', [ProfessorSubjectController::class, 'store'])->name('assignments.store');
    Route::delete('assignments/{assignment}', [ProfessorSubjectController::class, 'destroy'])->name('assignments.destroy');

    Route::get('logs', [LogController::class, 'index'])->name('logs.index');

    // Profile/Settings
    Route::get('settings', [AdminProfileController::class, 'edit'])->name('settings');
    Route::put('settings', [AdminProfileController::class, 'update'])->name('settings.update');
    Route::put('settings/password', [AdminProfileController::class, 'updatePassword'])->name('settings.password');
});

// Student Routes
Route::middleware(['auth', 'student'])->prefix('student')->as('student.')->group(function () {
    Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');

    // Subjects
    Route::get('subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
    Route::post('subjects/join', [StudentSubjectController::class, 'store'])->name('subjects.join');

    // Assessments
    Route::get('assessments', [StudentAssessmentController::class, 'index'])->name('assessments.index');
    Route::get('assessments/{assessment}/take', [StudentAssessmentController::class, 'show'])->name('assessments.show');
    Route::post('assessments/{assessment}/submit', [StudentAssessmentController::class, 'store'])->name('assessments.store');
    Route::get('assessments/{assessment}/history', [StudentAssessmentController::class, 'history'])->name('assessments.history');
    Route::get('assessments/{assessment}/results/{attempt}', [StudentAssessmentController::class, 'results'])->name('assessments.results');

    // Notifications
    Route::get('notifications/unread', [StudentNotificationController::class, 'index'])->name('notifications.unread');
    Route::post('notifications/{notification}/read', [StudentNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [StudentNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Profile/Settings
    Route::get('settings', [StudentProfileController::class, 'edit'])->name('settings');
    Route::put('settings', [StudentProfileController::class, 'update'])->name('settings.update');
    Route::put('settings/password', [StudentProfileController::class, 'updatePassword'])->name('settings.password');
});

// Instructor Routes
Route::middleware(['auth', 'instructor'])->prefix('instructor')->as('instructor.')->group(function () {
    Route::get('/', [InstructorDashboardController::class, 'index'])->name('dashboard');


    // Subjects & Join Requests
    Route::get('subjects', [\App\Http\Controllers\Instructor\SubjectController::class, 'index'])->name('subjects.index');
    Route::get('subjects/{subject}/requests', [\App\Http\Controllers\Instructor\SubjectController::class, 'requests'])->name('subjects.requests');
    Route::post('subjects/{subject}/requests/{studentSubject}/approve', [\App\Http\Controllers\Instructor\SubjectController::class, 'approve'])->name('subjects.requests.approve');
    Route::post('subjects/{subject}/requests/{studentSubject}/decline', [\App\Http\Controllers\Instructor\SubjectController::class, 'decline'])->name('subjects.requests.decline');

    // Lessons - Manual Creation
    Route::get('lessons/create-manual', [\App\Http\Controllers\Instructor\LessonController::class, 'createManual'])->name('lessons.createManual');
    Route::post('lessons/store-manual', [\App\Http\Controllers\Instructor\LessonController::class, 'storeManual'])->name('lessons.storeManual');

    // Lessons
    Route::resource('lessons', \App\Http\Controllers\Instructor\LessonController::class)->except(['show']);
    Route::post('lessons/{lesson}/publish', [\App\Http\Controllers\Instructor\LessonController::class, 'publish'])->name('lessons.publish');
    Route::post('lessons/{lesson}/unpublish', [\App\Http\Controllers\Instructor\LessonController::class, 'unpublish'])->name('lessons.unpublish');

    // Lesson Review (session-based)
    Route::get('lessons/review', [\App\Http\Controllers\Instructor\LessonController::class, 'review'])->name('lessons.review');
    Route::post('lessons/review/save', [\App\Http\Controllers\Instructor\LessonController::class, 'saveFromReview'])->name('lessons.review.save');
    Route::post('lessons/review/cancel', [\App\Http\Controllers\Instructor\LessonController::class, 'cancelReview'])->name('lessons.review.cancel');

    // Notifications
    Route::get('notifications/unread', [InstructorNotificationController::class, 'index'])->name('notifications.unread');
    Route::post('notifications/{notification}/read', [InstructorNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [InstructorNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Profile/Settings
    Route::get('settings', [InstructorProfileController::class, 'edit'])->name('settings');
    Route::put('settings', [InstructorProfileController::class, 'update'])->name('settings.update');
    Route::put('settings/password', [InstructorProfileController::class, 'updatePassword'])->name('settings.password');
});

require __DIR__ . '/auth.php';
