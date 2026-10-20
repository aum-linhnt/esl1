<?php

namespace App\Console\Commands;

use App\Models\{Course, Enrollment, LearningGoal, User};
use Database\Seeders\AiTutorDemo\AiTutorIeltsDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PrepareIeltsShowcaseStudent extends Command
{
    protected $signature = 'demo:ielts-student {email : Email học viên dùng cho demo local}';
    protected $description = 'Chuẩn bị học viên cho khóa IELTS 6.5 demo, không reset mật khẩu hoặc tiến độ đã có';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Chỉ chạy ở môi trường local hoặc testing.');
            return self::FAILURE;
        }
        $email = trim((string) $this->argument('email'));
        if (Validator::make(['email' => $email], ['email' => 'required|email|max:255'])->fails()) {
            $this->error('Email không hợp lệ.');
            return self::FAILURE;
        }
        $course = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        if (! $course->is_published || ! str_contains($course->description ?? '', 'AI_TUTOR_IELTS_DEMO_V1')) {
            $this->error('Hãy chuẩn hóa khóa mẫu bằng AiTutorIeltsShowcaseDemoSeeder trước.');
            return self::FAILURE;
        }
        $existing = User::where('email', $email)->first();
        if ($existing && (! $existing->isStudent() || ! $existing->isActive())) {
            $this->error('Tài khoản đã có phải là học viên đang hoạt động; không tự đổi vai trò hoặc trạng thái.');
            return self::FAILURE;
        }
        $enrollment = $existing?->getEnrollment($course->id);
        if ($enrollment && (! $enrollment->hasValidAccess() || $enrollment->course_role !== 'student')) {
            $this->error('Ghi danh hiện tại không hợp lệ cho demo học viên; không tự thay đổi quyền đã có.');
            return self::FAILURE;
        }
        $password = $existing ? null : Str::password(20);
        $student = DB::transaction(function () use ($email, $password, $existing, $course) {
            $student = $existing ?? User::create([
                'name' => 'Học viên demo IELTS', 'username' => 'ielts_demo_'.Str::lower(Str::random(12)),
                'email' => $email, 'password' => $password, 'role' => 'student', 'status' => 'active',
                'current_level' => 'B1', 'target_level' => 'B2',
            ]);
            if (! $existing) $student->forceFill(['email_verified_at' => now()])->save();
            Enrollment::firstOrCreate(['course_id' => $course->id, 'user_id' => $student->id], [
                'course_role' => 'student', 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0,
            ]);
            LearningGoal::firstOrCreate(['user_id' => $student->id], ['framework' => 'ielts', 'target' => '6.5']);
            return $student;
        });
        $this->info('Đã ghi danh học viên #'.$student->id.' vào IELTS 6.5: '.url('/courses/'.$course->id));
        if ($password) $this->line('Mật khẩu tạm (chỉ hiển thị khi tạo mới): '.$password);
        else $this->info('Giữ nguyên mật khẩu và tiến độ hiện tại.');
        return self::SUCCESS;
    }
}
