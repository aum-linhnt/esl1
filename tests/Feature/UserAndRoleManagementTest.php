<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAndRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Lê Hoàng Nam',
            'username' => 'hoangnam',
            'email' => 'hoangnam@fsel.vn',
            'password' => 'secret123',
            'role' => 'student',
            'status' => 'active',
            'current_level' => 'B1',
            'coins' => 50,
            'trial_days' => 7,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'hoangnam',
            'email' => 'hoangnam@fsel.vn',
            'current_level' => 'B1',
            'coins' => 50,
        ]);

        $createdUser = User::where('username', 'hoangnam')->first();
        $this->assertTrue($createdUser->hasRole('student'));
    }

    public function test_admin_can_view_user_profile(): void
    {
        $admin = User::where('username', 'admin')->first();
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($admin)->get("/admin/users/{$student->id}");

        $response->assertStatus(200);
        $response->assertSee('Hồ sơ Học tập');
        $response->assertSee($student->name);
        $response->assertSee('Ma trận Chẩn đoán 4 Kỹ năng');
    }

    public function test_admin_can_update_user_and_permissions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($admin)->put("/admin/users/{$student->id}", [
            'name' => 'Tuấn Linh Pro',
            'username' => 'tuanlinh',
            'email' => 'tuanlinh@fsel.vn',
            'role' => 'student',
            'status' => 'active',
            'current_level' => 'B2',
            'coins' => 100,
            'permissions' => ['view courses', 'access marketplace'],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertEquals('Tuấn Linh Pro', $student->fresh()->name);
        $this->assertEquals('B2', $student->fresh()->current_level);
        $this->assertEquals(100, $student->fresh()->coins);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::where('username', 'admin')->first();
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($admin)->delete("/admin/users/{$student->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_create_custom_role_with_permissions(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'teaching_assistant',
            'permissions' => ['view courses', 'view lessons', 'view question bank', 'create questions'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'teaching_assistant']);

        $role = Role::findByName('teaching_assistant');
        $this->assertTrue($role->hasPermissionTo('create questions'));
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $role = Role::findByName('teacher');

        $response = $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
            'name' => 'teacher',
            'permissions' => ['view courses', 'create courses', 'view reports'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertTrue($role->fresh()->hasPermissionTo('view reports'));
    }

    public function test_admin_cannot_delete_core_roles(): void
    {
        $admin = User::where('username', 'admin')->first();
        $adminRole = Role::findByName('admin');

        $response = $this->actingAs($admin)->delete("/admin/roles/{$adminRole->id}");

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_admin_can_delete_custom_role(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customRole = Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->delete("/admin/roles/{$customRole->id}");

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['name' => 'reviewer']);
    }
}
