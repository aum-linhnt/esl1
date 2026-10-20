<?php

namespace Tests\Feature;

use App\Models\{Activity, Course, User};
use Database\Seeders\AiTutorDemo\{AiTutorIeltsDemoSeeder, AiTutorIeltsMediaDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorIeltsMediaDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_is_repeatable_and_student_can_play_and_complete_first_lesson_media(): void
    {
        $this->seed(AiTutorIeltsMediaDemoSeeder::class);
        $course = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        $ids = $course->lessons->pluck('id');
        $activities = Activity::whereIn('lesson_id', $ids)->get();
        $this->assertCount(27, $activities);
        $this->assertCount(5, $activities->where('type', 'video'));
        $this->assertCount(5, $activities->where('type', 'pdf_document'));
        $this->assertCount(2, $activities->where('type', 'audio_listening'));
        $messages = DB::table('tutor_ai_conversation_messages')->count();
        $this->seed(AiTutorIeltsMediaDemoSeeder::class);
        $this->assertSame(27, Activity::whereIn('lesson_id', $ids)->count());
        $this->assertSame($messages, DB::table('tutor_ai_conversation_messages')->count());
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->artisan('demo:ielts-student', ['email' => $student->email])->assertSuccessful();
        $lesson = $course->lessons()->orderBy('order')->firstOrFail();
        $this->actingAs($student);
        foreach ($lesson->activities()->whereIn('type', ['audio_listening', 'video', 'pdf_document'])->get() as $activity) {
            $response = $this->get('/activities/'.$activity->id)->assertOk();
            if ($activity->type === 'video') {
                $response->assertSee('<video controls', false)->assertSee('listening-guide.mp4')->assertSee('listening-guide.vtt');
            } elseif ($activity->type === 'audio_listening') {
                $response->assertSee('listening-registration.mp3')->assertSee('Maya Chen')->assertSee('giọng tổng hợp');
            } else {
                $response->assertSee('listening-worksheet.pdf');
            }
            $this->postJson('/activities/'.$activity->id.'/complete')->assertOk()->assertJsonPath('success', true);
        }
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
}
