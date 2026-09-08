<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissionNames = [
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
            'view lessons',
            'create lessons',
            'edit lessons',
            'delete lessons',
            'manage users',
            'manage roles',
            'view question bank',
            'create questions',
            'edit questions',
            'delete questions',
            'view reports',
            'access marketplace',
        ];

        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'web']);
        $studentRole->syncPermissions([
            $permissions['view courses'],
            $permissions['view lessons'],
            $permissions['view question bank'],
            $permissions['access marketplace'],
        ]);

        $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        $teacherRole->syncPermissions([
            $permissions['view courses'],
            $permissions['create courses'],
            $permissions['edit courses'],
            $permissions['view lessons'],
            $permissions['create lessons'],
            $permissions['edit lessons'],
            $permissions['view question bank'],
            $permissions['create questions'],
            $permissions['edit questions'],
            $permissions['view reports'],
            $permissions['access marketplace'],
        ]);

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());
    }
}
