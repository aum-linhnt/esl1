<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Seed course-level permissions and assign them to course context roles.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Course context permissions
        $coursePerms = [
            'view course content',
            'submit course activities',
            'bypass lesson locks',
            'grade course submissions',
            'view course gradebook',
            'manage course curriculum',
            'manage course enrollments',
            'suspend course participants',
        ];

        $perms = [];
        foreach ($coursePerms as $name) {
            $perms[$name] = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 1. Course Student
        $cStudent = Role::firstOrCreate(['name' => 'course_student', 'guard_name' => 'web']);
        $cStudent->syncPermissions([
            $perms['view course content'],
            $perms['submit course activities'],
        ]);

        // 2. Course Assistant
        $cAssistant = Role::firstOrCreate(['name' => 'course_assistant', 'guard_name' => 'web']);
        $cAssistant->syncPermissions([
            $perms['view course content'],
            $perms['bypass lesson locks'],
            $perms['grade course submissions'],
            $perms['view course gradebook'],
        ]);

        // 3. Course Teacher
        $cTeacher = Role::firstOrCreate(['name' => 'course_teacher', 'guard_name' => 'web']);
        $cTeacher->syncPermissions([
            $perms['view course content'],
            $perms['bypass lesson locks'],
            $perms['grade course submissions'],
            $perms['view course gradebook'],
            $perms['manage course curriculum'],
        ]);

        // 4. Course Manager
        $cManager = Role::firstOrCreate(['name' => 'course_manager', 'guard_name' => 'web']);
        $cManager->syncPermissions([
            $perms['view course content'],
            $perms['bypass lesson locks'],
            $perms['grade course submissions'],
            $perms['view course gradebook'],
            $perms['manage course curriculum'],
            $perms['manage course enrollments'],
            $perms['suspend course participants'],
        ]);

        // Also assign all to admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep permissions intact
    }
};
