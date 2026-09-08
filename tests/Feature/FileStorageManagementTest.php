<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\File;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Storage\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected FileStorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->storageService = app(FileStorageService::class);
        Storage::fake('public');
    }

    public function test_file_storage_stores_in_partitioned_folder(): void
    {
        $file = UploadedFile::fake()->create('listening-track.mp3', 500, 'audio/mpeg');

        $record = $this->storageService->store($file, 'questions/audio');

        $this->assertInstanceOf(File::class, $record);
        $this->assertEquals('questions/audio', $record->folder);
        $this->assertStringStartsWith('questions/audio/', $record->storage_path);
        $this->assertEquals(1, $record->reference_count);
        $this->assertFalse($record->is_temp);
        Storage::disk('public')->assertExists($record->storage_path);
    }

    public function test_cas_deduplication_and_reference_counting(): void
    {
        $content = 'identical-avatar-binary-content-12345';
        $file1 = UploadedFile::fake()->createWithContent('avatar1.jpg', $content);
        $file2 = UploadedFile::fake()->createWithContent('avatar2.jpg', $content);

        $record1 = $this->storageService->store($file1, 'avatars');
        $this->assertEquals(1, $record1->reference_count);

        // Second upload with identical content should reuse record and increment ref count
        $record2 = $this->storageService->store($file2, 'avatars');
        $this->assertEquals($record1->id, $record2->id);
        $this->assertEquals(2, $record2->reference_count);

        // Delete first reference -> count decrements to 1, file remains
        $deleted = $this->storageService->delete($record1);
        $this->assertFalse($deleted);
        $record1->refresh();
        $this->assertEquals(1, $record1->reference_count);
        Storage::disk('public')->assertExists($record1->storage_path);

        // Delete second reference -> record deleted and physical file removed
        $deleted = $this->storageService->delete($record1);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('files', ['id' => $record1->id]);
        Storage::disk('public')->assertMissing($record1->storage_path);
    }

    public function test_temporary_file_storage_and_promotion(): void
    {
        $file = UploadedFile::fake()->create('temp_audio.wav', 300, 'audio/wav');

        $tempRecord = $this->storageService->storeTemp($file, 12);

        $this->assertTrue($tempRecord->is_temp);
        $this->assertEquals('temp', $tempRecord->folder);
        $this->assertStringStartsWith('temp/', $tempRecord->storage_path);
        $this->assertNotNull($tempRecord->expires_at);
        Storage::disk('public')->assertExists($tempRecord->storage_path);

        $oldPath = $tempRecord->storage_path;

        // Promote temp file to permanent question audio
        $promoted = $this->storageService->promoteTemp($tempRecord, 'questions/audio');

        $this->assertFalse($promoted->is_temp);
        $this->assertNull($promoted->expires_at);
        $this->assertEquals('questions/audio', $promoted->folder);
        $this->assertStringStartsWith('questions/audio/', $promoted->storage_path);

        // Old temp path should not exist, new promoted path must exist
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($promoted->storage_path);
    }

    public function test_clean_temp_files_artisan_command(): void
    {
        $file = UploadedFile::fake()->create('stale_temp.mp3', 200, 'audio/mpeg');
        $tempRecord = $this->storageService->storeTemp($file, 24);

        // Backdate expires_at and created_at
        $tempRecord->update([
            'expires_at' => now()->subHours(25),
            'created_at' => now()->subHours(25),
        ]);

        Storage::disk('public')->assertExists($tempRecord->storage_path);

        // Run artisan command
        $this->artisan('storage:clean-temp', ['--hours' => 24])
            ->expectsOutputToContain('Cleaned up 1 temporary file(s) successfully.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('files', ['id' => $tempRecord->id]);
        Storage::disk('public')->assertMissing($tempRecord->storage_path);
    }

    public function test_file_api_upload_and_upload_temp_endpoints(): void
    {
        // 1. Permanent Upload
        $file = UploadedFile::fake()->image('thumbnail.png', 400, 300);
        $response = $this->postJson(route('api.v1.files.upload'), [
            'file' => $file,
            'folder' => 'courses/thumbnails',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.folder', 'courses/thumbnails');

        // 2. Temp Upload
        $tempFile = UploadedFile::fake()->create('recording.webm', 100, 'audio/webm');
        $tempResponse = $this->postJson(route('api.v1.files.upload-temp'), [
            'file' => $tempFile,
            'ttl_hours' => 6,
        ]);

        $tempResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_temp', true);
    }

    public function test_question_bank_audio_and_image_upload(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $audio = UploadedFile::fake()->create('question_audio.mp3', 400, 'audio/mpeg');
        $image = UploadedFile::fake()->image('question_diagram.png', 500, 400);

        $response = $this->post(route('admin.questions.store'), [
            'skill' => 'listening',
            'difficulty' => 'B1',
            'question_type' => 'audio_listening',
            'question_text' => 'Listen to the conversation and answer.',
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_answer' => 'Option A',
            'audio_file' => $audio,
            'image_file' => $image,
        ]);

        $response->assertRedirect(route('admin.questions.index'));

        $question = QuestionBank::where('skill', 'listening')->latest('id')->first();
        $this->assertNotNull($question);
        $this->assertStringContainsString('/storage/questions/audio/', $question->audio_url);
        $this->assertNotNull($question->meta_data['image_url']);
        $this->assertStringContainsString('/storage/questions/images/', $question->meta_data['image_url']);
    }

    public function test_question_bank_testlet_audio_upload(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $audio = UploadedFile::fake()->create('testlet_audio.mp3', 800, 'audio/mpeg');

        $response = $this->post(route('admin.questions.storeTestlet'), [
            'skill' => 'listening',
            'difficulty' => 'B2',
            'passage_title' => 'Lecture on Marine Biology',
            'passage_content' => 'Full lecture script about coral reef ecosystems...',
            'audio_file' => $audio,
            'part' => 1,
            'questions' => [
                [
                    'question_text' => 'What is the main topic of the lecture?',
                    'opt_a' => 'Deep ocean currents',
                    'opt_b' => 'Coral bleaching causes',
                    'opt_c' => 'Whale migrations',
                    'opt_d' => 'Submarine technologies',
                    'correct_answer' => 'B',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.questions.index', ['tab' => 'testlets']));

        $question = QuestionBank::where('meta_data->passage_title', 'Lecture on Marine Biology')->first();
        $this->assertNotNull($question);
        $this->assertStringContainsString('/storage/questions/audio/', $question->audio_url);
    }

    public function test_user_avatar_upload_in_profile(): void
    {
        $user = User::where('role', 'student')->first();
        $this->actingAs($user);

        $avatar = UploadedFile::fake()->image('my_avatar.jpg', 200, 200);

        $response = $this->patch(route('profile.update'), [
            'name' => 'Updated User Name',
            'email' => $user->email,
            'avatar' => $avatar,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        $this->assertStringContainsString('/storage/avatars/', $user->avatar_url);
    }

    public function test_admin_user_avatar_upload(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $avatar = UploadedFile::fake()->image('new_user_avatar.png', 150, 150);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Student User',
            'username' => 'teststudent123',
            'email' => 'teststudent123@example.com',
            'password' => 'secret123',
            'role' => 'student',
            'status' => 'active',
            'current_level' => 'A1',
            'coins' => 50,
            'avatar' => $avatar,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $newUser = User::where('username', 'teststudent123')->first();
        $this->assertNotNull($newUser);
        $this->assertStringStartsWith('avatars/', $newUser->avatar);
    }

    public function test_course_thumbnail_upload(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $thumb = UploadedFile::fake()->image('course_banner.png', 800, 500);

        $response = $this->post(route('admin.courses.store'), [
            'title' => 'IELTS Intensive Preparation',
            'level' => 'B2',
            'target_audience' => 'Students aiming for 7.0+',
            'order' => 1,
            'thumbnail_file' => $thumb,
        ]);

        $course = Course::where('title', 'IELTS Intensive Preparation')->first();
        $this->assertNotNull($course);
        $response->assertRedirect(route('admin.courses.show', $course->id));

        $this->assertStringStartsWith('courses/thumbnails/', $course->thumbnail);
        $this->assertStringContainsString('/storage/courses/thumbnails/', $course->thumbnail_url);
    }
}
