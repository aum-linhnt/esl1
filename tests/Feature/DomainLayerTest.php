<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\CourseRole;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\MessageSent;
use App\Models\Activity;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Services\ActionLogService;
use App\Services\MessagingService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DomainLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_enums_contain_expected_cases_and_labels(): void
    {
        $this->assertSame('Quản trị viên', UserRole::ADMIN->label());
        $this->assertSame('Giảng viên', UserRole::TEACHER->label());
        $this->assertSame('Học viên', UserRole::STUDENT->label());
        $this->assertContains('admin', UserRole::values());

        $this->assertSame('Đang hoạt động', UserStatus::ACTIVE->label());
        $this->assertSame('Bị khóa', UserStatus::BLOCKED->label());

        $this->assertSame('Học viên', CourseRole::STUDENT->label());
        $this->assertSame('Giảng viên phụ trách', CourseRole::TEACHER->label());

        $this->assertSame('Bài tập trắc nghiệm', ActivityType::QUIZ->label());
        $this->assertSame('Tin nhắn', NotificationType::MESSAGE->label());
    }

    public function test_services_can_be_resolved_via_di_and_static_proxy(): void
    {
        $messagingService = app(MessagingService::class);
        $this->assertInstanceOf(MessagingService::class, $messagingService);

        $notificationService = app(NotificationService::class);
        $this->assertInstanceOf(NotificationService::class, $notificationService);

        $actionLogService = app(ActionLogService::class);
        $this->assertInstanceOf(ActionLogService::class, $actionLogService);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);

        // Test ActionLogService via DI & static proxy
        $log = ActionLogService::logLogin($student);
        $this->assertSame('login', $log->action);
        $this->assertSame($student->id, $log->user_id);

        // Test NotificationService via DI & static proxy
        $notification = NotificationService::send($student, 'Chào mừng', 'Nội dung thông báo');
        $this->assertSame('Chào mừng', $notification->title);
        $this->assertSame(1, NotificationService::getUnreadCount($student));
    }

    public function test_course_policy_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'English Grammar 101',
            'slug' => 'english-grammar-101',
            'created_by' => $teacher->id,
            'is_published' => true,
        ]);

        // Admin can update any course
        $this->assertTrue(Gate::forUser($admin)->allows('update', $course));

        // Creator teacher can update
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $course));

        // Unrelated teacher cannot update
        $this->assertFalse(Gate::forUser($otherTeacher)->allows('update', $course));

        // Student cannot update
        $this->assertFalse(Gate::forUser($student)->allows('update', $course));
    }

    public function test_message_policy_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $unrelatedTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Speaking Master',
            'slug' => 'speaking-master',
            'created_by' => $teacher->id,
        ]);

        // Enroll student in teacher's course
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'course_role' => 'student',
            'status' => 'active',
        ]);

        $messagePolicy = new \App\Policies\MessagePolicy();

        // Student CAN message their course teacher
        $this->assertTrue($messagePolicy->initiateDirectMessage($student, $teacher));

        // Student CANNOT message an unrelated teacher
        $this->assertFalse($messagePolicy->initiateDirectMessage($student, $unrelatedTeacher));

        // Student CANNOT message another student
        $this->assertFalse($messagePolicy->initiateDirectMessage($student, $otherStudent));

        // Teacher CAN message their enrolled student
        $this->assertTrue($messagePolicy->initiateDirectMessage($teacher, $student));

        // Teacher CANNOT message an unenrolled student
        $this->assertFalse($messagePolicy->initiateDirectMessage($teacher, $otherStudent));

        // Admin CAN message anyone
        $this->assertTrue($messagePolicy->initiateDirectMessage($admin, $student));
    }

    public function test_assignment_submission_policy_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Writing Skills',
            'slug' => 'writing-skills',
            'created_by' => $teacher->id,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson 1: Essay Intro',
            'order' => 1,
        ]);

        $activity = Activity::create([
            'lesson_id' => $lesson->id,
            'title' => 'Writing Assignment 1',
            'type' => 'assignment',
            'order' => 1,
        ]);

        $submission = AssignmentSubmission::create([
            'activity_id' => $activity->id,
            'user_id' => $student->id,
            'text_content' => 'My first essay draft.',
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);

        // Student can view their own submission
        $this->assertTrue(Gate::forUser($student)->allows('view', $submission));

        // Other student cannot view
        $this->assertFalse(Gate::forUser($otherStudent)->allows('view', $submission));

        // Course teacher can view and grade
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $submission));
        $this->assertTrue(Gate::forUser($teacher)->allows('grade', $submission));

        // Unrelated teacher cannot grade
        $this->assertFalse(Gate::forUser($otherTeacher)->allows('grade', $submission));

        // Admin can grade
        $this->assertTrue(Gate::forUser($admin)->allows('grade', $submission));
    }

    public function test_sending_message_dispatches_event_and_creates_notification(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Thầy Hoàng']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Em Lan']);

        $course = Course::create([
            'title' => 'IELTS Intensive',
            'slug' => 'ielts-intensive',
            'created_by' => $teacher->id,
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'course_role' => 'student',
            'status' => 'active',
        ]);

        /** @var MessagingService $messagingService */
        $messagingService = app(MessagingService::class);

        $message = $messagingService->sendDirectMessage($teacher, $student, 'Chào em, nhớ nộp bài nhé!');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('Chào em, nhớ nộp bài nhé!', $message->body);

        // Check that notification was automatically sent to student via listener
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'sender_id' => $teacher->id,
            'title' => 'Tin nhắn mới từ Thầy Hoàng',
        ]);
    }
}
