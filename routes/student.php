<?php

use App\Http\Controllers\ActivityCompletionController;
use App\Http\Controllers\AdaptiveTestController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AssignmentSubmissionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuizApiController;
use App\Http\Controllers\SpeakingPracticeController;
use App\Http\Controllers\WritingPracticeController;
use Illuminate\Support\Facades\Route;

// ─── Authenticated Student / Learner Routes ───
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard & Renewal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/renew', [DashboardController::class, 'renew'])->name('renew');

    // Courses (Menu: Học)
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{courseId}', [CourseController::class, 'show'])->name('courses.show');

    // Lessons & Activities
    Route::get('/lessons/{lessonId}', [LessonController::class, 'show'])->name('lessons.show');
    Route::get('/activities/{activityId}', [LessonController::class, 'showActivity'])->name('activities.show');
    Route::post('/lessons/{lessonId}/complete', [LessonController::class, 'complete'])->name('lessons.complete');

    // Practice & Static Exam
    Route::get('/practice', [PracticeController::class, 'index'])->name('practice.index');
    Route::get('/practice/exam/{testKey}', [PracticeController::class, 'showExam'])->name('practice.exam');
    Route::post('/practice/exam/{testKey}/submit', [PracticeController::class, 'submitExam'])->name('practice.exam.submit');
    Route::get('/practice/exam/{testKey}/attempts/{submissionId}', [PracticeController::class, 'reviewAttempt'])->name('practice.exam.attempt.review');

    // Adaptive Testing
    Route::post('/practice/check', [QuizApiController::class, 'checkAnswer'])->name('practice.check');
    Route::post('/practice/start-adaptive', [AdaptiveTestController::class, 'startAdaptive'])->name('practice.startAdaptive');
    Route::get('/practice/adaptive/start/{testKey?}', [AdaptiveTestController::class, 'startAdaptiveByKey'])->name('practice.adaptive.startByKey');
    Route::get('/practice/player/{session}', [AdaptiveTestController::class, 'player'])->name('practice.player');
    Route::get('/practice/adaptive/{session}/scorecard', [AdaptiveTestController::class, 'adaptiveScorecard'])->name('practice.adaptive.scorecard');

    // AI Modules
    Route::post('/api/ai/chat', [AiChatController::class, 'message'])->name('api.ai.chat');
    
    Route::get('/ai/writing', [WritingPracticeController::class, 'index'])->name('ai.writing.index');
    Route::post('/ai/writing/analyze', [WritingPracticeController::class, 'analyze'])->name('api.ai.writing.analyze');

    Route::get('/ai/speaking', [SpeakingPracticeController::class, 'index'])->name('ai.speaking.index');
    Route::post('/ai/speaking/evaluate', [SpeakingPracticeController::class, 'evaluate'])->name('api.ai.speaking.evaluate');

    // Certificates
    Route::get('/certificates/{courseId}', [CertificateController::class, 'show'])->name('certificates.show');

    // JSON API for Adaptive & Telemetry
    Route::post('/api/adaptive/next-question', [AdaptiveTestController::class, 'fetchNextQuestion'])->name('api.adaptive.next');
    Route::post('/api/adaptive/submit-answer', [AdaptiveTestController::class, 'submitAdaptiveAnswer'])->name('api.adaptive.submit');
    Route::post('/api/activity/log-step', [QuizApiController::class, 'logActivityStep'])->name('api.activity.log');

    // Enrollment (Ghi danh)
    Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enroll'])->name('courses.enroll');
    Route::post('/courses/{courseId}/unenroll', [EnrollmentController::class, 'unenroll'])->name('courses.unenroll');
    Route::get('/my-courses', [EnrollmentController::class, 'myCourses'])->name('enrollments.myCourses');

    // Gradebook (Sổ điểm)
    Route::get('/gradebook', [GradebookController::class, 'index'])->name('gradebook.index');
    Route::get('/gradebook/{courseId}', [GradebookController::class, 'courseDetail'])->name('gradebook.show');

    // Activity Completion (Hoàn thành hoạt động)
    Route::post('/activities/{activityId}/complete', [ActivityCompletionController::class, 'complete'])->name('activities.complete');
    Route::get('/api/lesson/{lessonId}/completion-status', [ActivityCompletionController::class, 'status'])->name('api.lesson.completion');

    // Assignment Submissions (Student)
    Route::post('/activities/{activityId}/submit-assignment', [AssignmentSubmissionController::class, 'submit'])->name('activities.submitAssignment');
    Route::get('/activities/{activityId}/my-submissions', [AssignmentSubmissionController::class, 'mySubmissions'])->name('activities.mySubmissions');
    Route::get('/assignment-submissions/{submissionId}/download', [AssignmentSubmissionController::class, 'downloadFile'])->name('assignmentSubmissions.download');

    // Progress (Menu: Tiến trình)
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');

    // Leaderboard (Bảng Xếp Hạng XP & Streak)
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Marketplace & Invite
    Route::get('/marketplace', function () {
        return view('marketplace.index');
    })->name('marketplace.index');

    Route::get('/invite', function () {
        return view('invite.index');
    })->name('invite.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Centralized File Upload (Web Session)
    Route::post('/files/upload', [\App\Http\Controllers\FileController::class, 'upload'])->name('files.upload');
    Route::post('/files/upload-temp', [\App\Http\Controllers\FileController::class, 'uploadTemp'])->name('files.upload-temp');
});

