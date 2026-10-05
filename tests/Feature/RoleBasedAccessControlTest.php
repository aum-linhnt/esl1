<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_admin_portal(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_student_is_forbidden_from_admin_portal(): void
    {
        $student = User::where('role', 'student')->first();
        $this->assertNotNull($student);

        // Dashboard
        $response = $this->actingAs($student)->get('/admin');
        $response->assertStatus(403);

        // User management
        $userList = $this->actingAs($student)->get('/admin/users');
        $userList->assertStatus(403);

        // Course management
        $courses = $this->actingAs($student)->get('/admin/courses');
        $courses->assertStatus(403);
    }

    public function test_student_is_forbidden_from_teacher_ai_generator(): void
    {
        $student = User::where('role', 'student')->first();

        $response = $this->actingAs($student)->get('/teacher/ai-generator');
        $response->assertStatus(403);
    }

    public function test_teacher_can_access_admin_dashboard_and_ai_generator(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $this->assertNotNull($teacher);

        $dashboard = $this->actingAs($teacher)->get('/admin');
        $dashboard->assertStatus(200);

        $generator = $this->actingAs($teacher)->get('/teacher/ai-generator');
        $generator->assertStatus(200);
    }

    public function test_teacher_is_forbidden_from_admin_sensitive_subsystems(): void
    {
        $teacher = User::where('role', 'teacher')->first();

        // Users
        $users = $this->actingAs($teacher)->get('/admin/users');
        $users->assertStatus(403);

        // Roles
        $roles = $this->actingAs($teacher)->get('/admin/roles');
        $roles->assertStatus(403);

        // Settings
        $settings = $this->actingAs($teacher)->get('/admin/settings');
        $settings->assertStatus(403);

        // Logs
        $logs = $this->actingAs($teacher)->get('/admin/logs');
        $logs->assertStatus(403);
    }

    public function test_admin_has_full_access_to_all_subsystems(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin)->get('/admin')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/roles')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/settings')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/logs')->assertStatus(200);
        $this->actingAs($admin)->get('/teacher/ai-generator')->assertStatus(200);
    }

    public function test_unauthenticated_api_v1_requests_are_rejected(): void
    {
        $response = $this->postJson('/api/v1/files/upload', []);
        $response->assertStatus(401);

        $notif = $this->getJson('/api/v1/notifications');
        $notif->assertStatus(401);

        $messages = $this->getJson('/api/v1/messages/conversations');
        $messages->assertStatus(401);
    }
}
