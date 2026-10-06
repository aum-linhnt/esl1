<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\{Activity, Course};
use Illuminate\Support\Facades\DB;

class AiTutorIeltsMediaDemoSeeder extends AiTutorIeltsShowcaseDemoSeeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        $directory = public_path('demo/ielts-65');
        $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['version'] ?? '') !== 'IELTS_65_MEDIA_V1') throw new \RuntimeException('Manifest media demo không hợp lệ.');
        foreach ($manifest['files'] as $name => $meta) {
            if (basename($name) !== $name || ! is_file($directory.'/'.$name) || hash_file('sha256', $directory.'/'.$name) !== $meta['sha256']) {
                throw new \RuntimeException('Thiếu hoặc sai checksum media: '.$name.'. Chạy scripts/build-ielts-demo-media.py trước.');
            }
        }
        DB::transaction(function () use ($directory) {
            parent::run();
            $course = Course::where('slug', self::SLUG)->firstOrFail();
            $lessons = $course->lessons()->orderBy('order')->get();
            $keys = ['listening', 'reading', 'writing-task-1', 'writing-task-2', 'speaking'];
            foreach ($lessons as $i => $lesson) {
                $key = $keys[$i];
                $this->media($lesson->id, 3, 'video', 'Video hướng dẫn: '.$lesson->title, [
                    'video_url' => '/demo/ielts-65/'.$key.'-guide.mp4', 'poster_url' => '/demo/ielts-65/'.$key.'-poster.jpg',
                    'captions_url' => '/demo/ielts-65/'.$key.'-guide.vtt',
                    'description' => 'Video slide tự tạo, có phụ đề tiếng Anh và giọng tổng hợp. Nội dung luyện tập rút gọn, không phải tài liệu IELTS chính thức.',
                ]);
                $this->media($lesson->id, 4, 'pdf_document', 'PDF bài tập: '.$lesson->title, [
                    'document_url' => '/demo/ielts-65/'.$key.'-worksheet.pdf',
                    'notes' => 'Phiếu bài tập demo: tài liệu, đề bài và hướng dẫn. Tải PDF để luyện tập hoặc in ra.',
                ]);
                if ($i === 0 || $i === 4) {
                    $listening = $i === 0;
                    $this->media($lesson->id, 5, 'audio_listening', $listening ? 'Audio hội thoại: Course Registration' : 'Audio mẫu Speaking: A Skill You Learned', [
                        'audio_url' => '/demo/ielts-65/'.($listening ? 'listening-registration.mp3' : 'speaking-model.mp3'),
                        'transcript' => file_get_contents($directory.'/'.($listening ? 'listening-transcript.txt' : 'speaking-model.txt')),
                        'hide_transcript' => true,
                        'description' => $listening ? 'Hội thoại demo bằng hai giọng tổng hợp. Nghe trước, mở transcript sau để kiểm tra. Audio khớp đề quiz Course Registration.'
                            : 'Bài nói mẫu bằng giọng tổng hợp, không phải bản ghi âm của học viên và không dùng để chấm phát âm hay band Speaking.',
                    ]);
                }
                if ($i === 0) {
                    $material = $lesson->activities()->where('order', 0)->firstOrFail();
                    $body = str_replace('Bài demo dùng transcript, chưa có audio.', 'Bài demo có audio hội thoại bằng giọng tổng hợp. Nghe audio trước; dùng transcript bên dưới để đối chiếu sau.', $material->content['body']);
                    $material->update(['content' => array_merge($material->content, ['body' => $body])]);
                    $assignment = $lesson->activities()->where('order', 2)->firstOrFail();
                    $assignment->update(['content' => array_merge($assignment->content, ['instructions' => str_replace('Listening — transcript, chưa có audio', 'Listening — audio demo có transcript', $assignment->content['instructions'])])]);
                }
            }
            $this->command?->info('Đã ghép 2 audio, 5 video có phụ đề và 5 PDF vào IELTS mẫu #'.$course->id.'.');
        });
    }

    private function media(int $lessonId, int $order, string $type, string $title, array $content): void
    {
        $activity = Activity::firstOrCreate(['lesson_id' => $lessonId, 'order' => $order], [
            'title' => $title, 'type' => $type, 'is_visible' => true, 'completion_type' => 'manual', 'estimated_minutes' => 3,
            'content' => array_merge(['demo_media' => 'IELTS_65_MEDIA_V1'], $content),
        ]);
        if ($activity->type !== $type || ($activity->content['demo_media'] ?? null) !== 'IELTS_65_MEDIA_V1') {
            throw new \RuntimeException('Hoạt động media đã tồn tại với nội dung khác; không ghi đè.');
        }
    }
}
