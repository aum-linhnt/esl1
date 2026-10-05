<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BladeComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_badge_component_renders(): void
    {
        $view = $this->blade('<x-role-badge role="admin" />');
        $view->assertSee('Quản trị viên');

        $viewTeacher = $this->blade('<x-role-badge role="teacher" />');
        $viewTeacher->assertSee('Giảng viên');

        $viewStudent = $this->blade('<x-role-badge role="student" />');
        $viewStudent->assertSee('Học viên');
    }

    public function test_user_avatar_component_renders(): void
    {
        $user = User::factory()->create(['name' => 'Trần Văn Nam', 'role' => 'student']);

        $view = $this->blade('<x-user-avatar :user="$user" size="md" />', ['user' => $user]);
        $view->assertSee('TN');
    }

    public function test_empty_state_component_renders(): void
    {
        $view = $this->blade('<x-empty-state title="Chưa có dữ liệu" description="Vui lòng thử lại sau" actionText="Quay lại" actionUrl="/home" />');
        $view->assertSee('Chưa có dữ liệu');
        $view->assertSee('Vui lòng thử lại sau');
        $view->assertSee('Quay lại');
    }

    public function test_stat_card_component_renders(): void
    {
        $view = $this->blade('<x-stat-card title="Tổng học viên" value="1,240" color="indigo" subtitle="Tăng 12% so với tháng trước" />');
        $view->assertSee('Tổng học viên');
        $view->assertSee('1,240');
        $view->assertSee('Tăng 12%');
    }
}
