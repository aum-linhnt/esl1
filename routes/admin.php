<?php

use App\Http\Controllers\Admin\AdminExamController;
use App\Http\Controllers\Admin\AdminQuestionBankController;
use App\Http\Controllers\Admin\CourseActivityController as AdminCourseActivityController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseEnrollmentController as AdminCourseEnrollmentController;
use App\Http\Controllers\Admin\CourseLessonController as AdminCourseLessonController;
use App\Http\Controllers\Admin\CourseQuestionController as AdminCourseQuestionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GamificationController as AdminGamificationController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AssignmentSubmissionController;
use Illuminate\Support\Facades\Route;

// ─── Dedicated Enterprise Admin Portal Routes ───
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // User Management (Full CRUD + Profile + Batch Actions)
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{userId}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
    Route::post('/users/{userId}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggleStatus');
    Route::post('/users/{userId}/coins', [AdminUserController::class, 'updateCoins'])->name('users.updateCoins');
    Route::post('/users/{userId}/activate', [AdminUserController::class, 'activateUser'])->name('users.activate');

    // Role & Permission Management
    Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [AdminRoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{id}/edit', [AdminRoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{id}', [AdminRoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');

    // Course Management (CRUD only)
    Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [AdminCourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{courseId}', [AdminCourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{courseId}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{courseId}', [AdminCourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{courseId}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{courseId}/toggle-publish', [AdminCourseController::class, 'togglePublish'])->name('courses.togglePublish');

    // Course Enrollment Management
    Route::post('/courses/{courseId}/enroll', [AdminCourseEnrollmentController::class, 'manualEnroll'])->name('courses.manualEnroll');
    Route::post('/courses/{courseId}/enrollments/{userId}/role', [AdminCourseEnrollmentController::class, 'updateEnrollmentRole'])->name('courses.updateEnrollmentRole');
    Route::post('/courses/{courseId}/enrollments/{userId}/expiry', [AdminCourseEnrollmentController::class, 'updateEnrollmentExpiry'])->name('courses.updateEnrollmentExpiry');
    Route::post('/courses/{courseId}/enrollments/{userId}/toggle-suspend', [AdminCourseEnrollmentController::class, 'toggleSuspendEnrollment'])->name('courses.toggleSuspendEnrollment');
    Route::delete('/courses/{courseId}/unenroll/{userId}', [AdminCourseEnrollmentController::class, 'manualUnenroll'])->name('courses.manualUnenroll');

    // Lesson Management
    Route::post('/courses/{courseId}/lessons', [AdminCourseLessonController::class, 'storeLesson'])->name('courses.lessons.store');
    Route::put('/courses/{courseId}/lessons/{lessonId}', [AdminCourseLessonController::class, 'updateLesson'])->name('courses.lessons.update');
    Route::delete('/courses/{courseId}/lessons/{lessonId}', [AdminCourseLessonController::class, 'destroyLesson'])->name('courses.lessons.destroy');
    Route::post('/courses/{courseId}/reorder-lessons', [AdminCourseLessonController::class, 'reorderLessons'])->name('courses.reorderLessons');
    Route::post('/lessons/{lessonId}/toggle-visibility', [AdminCourseLessonController::class, 'toggleLessonVisibility'])->name('lessons.toggleVisibility');
    Route::post('/lessons/{lessonId}/toggle-trial', [AdminCourseLessonController::class, 'toggleLessonTrial'])->name('lessons.toggleTrial');

    // Modern Learning Activity Management
    Route::post('/lessons/{lessonId}/activities', [AdminCourseActivityController::class, 'storeActivity'])->name('lessons.activities.store');
    Route::delete('/activities/{activityId}', [AdminCourseActivityController::class, 'destroyActivity'])->name('activities.destroy');
    Route::post('/lessons/{lessonId}/reorder-activities', [AdminCourseActivityController::class, 'reorderActivities'])->name('lessons.reorderActivities');
    Route::post('/activities/{activityId}/move', [AdminCourseActivityController::class, 'moveActivity'])->name('activities.move');
    Route::put('/activities/{activityId}', [AdminCourseActivityController::class, 'updateActivity'])->name('activities.update');
    Route::post('/activities/{activityId}/toggle-visibility', [AdminCourseActivityController::class, 'toggleActivityVisibility'])->name('activities.toggleVisibility');
    Route::post('/activities/{activityId}/toggle-trial', [AdminCourseActivityController::class, 'toggleActivityTrial'])->name('activities.toggleTrial');
    Route::post('/activities/{activityId}/duplicate', [AdminCourseActivityController::class, 'duplicateActivity'])->name('activities.duplicate');
    Route::post('/activities/upload-file', [AdminCourseActivityController::class, 'uploadActivityFile'])->name('activities.uploadFile');

    // Assignment Grading (Admin/Teacher)
    Route::get('/activities/{activityId}/submissions', [AssignmentSubmissionController::class, 'activitySubmissions'])->name('activities.submissions');
    Route::post('/assignment-submissions/{submissionId}/grade', [AssignmentSubmissionController::class, 'grade'])->name('assignmentSubmissions.grade');

    // Course-specific Question Bank Management
    Route::post('/courses/{courseId}/questions', [AdminCourseQuestionController::class, 'storeCourseQuestion'])->name('courses.questions.store');
    Route::put('/courses/{courseId}/questions/{questionId}', [AdminCourseQuestionController::class, 'updateCourseQuestion'])->name('courses.questions.update');
    Route::delete('/courses/{courseId}/questions/{questionId}', [AdminCourseQuestionController::class, 'deleteCourseQuestion'])->name('courses.questions.destroy');
    Route::post('/courses/{courseId}/questions/import', [AdminCourseQuestionController::class, 'importQuestionsFromGlobal'])->name('courses.questions.import');

    // Enterprise Question Bank & Testlet Studio Management
    Route::get('/questions', [AdminQuestionBankController::class, 'index'])->name('questions.index');
    Route::post('/questions', [AdminQuestionBankController::class, 'store'])->name('questions.store');
    Route::post('/questions/testlet', [AdminQuestionBankController::class, 'storeTestlet'])->name('questions.storeTestlet');
    Route::put('/questions/testlet/update', [AdminQuestionBankController::class, 'updateTestlet'])->name('questions.updateTestlet');
    Route::post('/questions/testlet/destroy', [AdminQuestionBankController::class, 'destroyTestlet'])->name('questions.destroyTestlet');
    Route::get('/questions/{id}/json', [AdminQuestionBankController::class, 'showJson'])->name('questions.showJson');
    Route::put('/questions/{id}', [AdminQuestionBankController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{id}', [AdminQuestionBankController::class, 'destroy'])->name('questions.destroy');
    Route::post('/questions/bulk-delete', [AdminQuestionBankController::class, 'bulkDestroy'])->name('questions.bulkDestroy');

    // Exam Management (Luyện đề & Khảo thí)
    Route::get('/exams', [AdminExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [AdminExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [AdminExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{id}/edit', [AdminExamController::class, 'edit'])->name('exams.edit');
    Route::put('/exams/{id}', [AdminExamController::class, 'update'])->name('exams.update');
    Route::delete('/exams/{id}', [AdminExamController::class, 'destroy'])->name('exams.destroy');
    Route::post('/exams/{id}/toggle-publish', [AdminExamController::class, 'togglePublish'])->name('exams.togglePublish');

    // Submission Monitoring
    Route::get('/submissions', [AdminSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{id}', [AdminSubmissionController::class, 'show'])->name('submissions.show');
    Route::delete('/submissions/{id}', [AdminSubmissionController::class, 'destroy'])->name('submissions.destroy');

    // Gamification & Badges
    Route::get('/gamification', [AdminGamificationController::class, 'index'])->name('gamification.index');
    Route::post('/gamification/award', [AdminGamificationController::class, 'awardBadge'])->name('gamification.award');
    Route::delete('/gamification/{id}', [AdminGamificationController::class, 'deleteBadge'])->name('gamification.delete');

    // LMS Reports (Báo cáo)
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/enrollments', [AdminReportController::class, 'enrollments'])->name('reports.enrollments');
    Route::get('/reports/grades', [AdminReportController::class, 'grades'])->name('reports.grades');
    Route::get('/reports/completions', [AdminReportController::class, 'completions'])->name('reports.completions');
    Route::get('/reports/export/{type}', [AdminReportController::class, 'exportCsv'])->name('reports.export');

    // LMS System Settings & AI Configuration
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/api-key', [AdminSettingController::class, 'updateApiKey'])->name('settings.updateApiKey');
    Route::post('/settings/test-connection', [AdminSettingController::class, 'testConnection'])->name('settings.testConnection');
    Route::post('/settings/clear-cache', [AdminSettingController::class, 'clearCache'])->name('settings.clearCache');
});
