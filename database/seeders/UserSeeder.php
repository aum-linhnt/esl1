<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        $admin = User::create([
            'name' => 'Admin ESL',
            'username' => 'admin',
            'email' => 'admin@fsel.vn',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'coins' => 999,
            'current_level' => 'C2',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Demo student (shown on Dashboard)
        $student = User::create([
            'name' => 'Tuấn Linh',
            'username' => 'tuanlinh',
            'email' => 'tuanlinh@fsel.vn',
            'password' => Hash::make('password'),
            'role' => 'student',
            'status' => 'active',
            'coins' => 24,
            'current_level' => 'A2',
            'trial_ends_at' => Carbon::now()->subDays(1), // Trial expired
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');

        // Demo teacher
        $teacher = User::create([
            'name' => 'Giáo viên Demo',
            'username' => 'teacher',
            'email' => 'teacher@fsel.vn',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => 'active',
            'coins' => 100,
            'current_level' => 'C1',
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('teacher');
    }
}
