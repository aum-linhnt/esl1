<?php

namespace App\Enums;

enum ActivityType: string
{
    case VOCABULARY = 'vocabulary';
    case GRAMMAR = 'grammar';
    case VIDEO = 'video';
    case QUIZ = 'quiz';
    case AUDIO_LISTENING = 'audio_listening';
    case PDF_DOCUMENT = 'pdf_document';
    case AI_SPEAKING = 'ai_speaking';
    case AI_WRITING = 'ai_writing';
    case FILE = 'file';
    case URL = 'url';
    case TEXT_PAGE = 'text_page';
    case ASSIGNMENT = 'assignment';
    case FORUM = 'forum';
    case LABEL = 'label';
    case H5P = 'h5p';

    public function label(): string
    {
        return match ($this) {
            self::VOCABULARY => 'Flashcard Từ vựng',
            self::GRAMMAR => 'Bài giảng Ngữ pháp',
            self::VIDEO => 'Video bài giảng',
            self::QUIZ => 'Bài tập trắc nghiệm',
            self::AUDIO_LISTENING => 'Audio / Podcast',
            self::PDF_DOCUMENT => 'Tài liệu / Slide',
            self::AI_SPEAKING => 'Luyện nói AI',
            self::AI_WRITING => 'Luyện viết AI',
            self::FILE => 'Tập tin tài liệu',
            self::URL => 'Liên kết ngoài',
            self::TEXT_PAGE => 'Trang nội dung',
            self::ASSIGNMENT => 'Bài tập tự luận',
            self::FORUM => 'Diễn đàn thảo luận',
            self::LABEL => 'Nhãn phân đoạn',
            self::H5P => 'Tương tác H5P',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VOCABULARY => '📖',
            self::GRAMMAR => '📐',
            self::VIDEO => '🎬',
            self::QUIZ => '❓',
            self::AUDIO_LISTENING => '🎧',
            self::PDF_DOCUMENT => '📑',
            self::AI_SPEAKING => '🎙️',
            self::AI_WRITING => '✍️',
            self::FILE => '📁',
            self::URL => '🔗',
            self::TEXT_PAGE => '📝',
            self::ASSIGNMENT => '📋',
            self::FORUM => '💬',
            self::LABEL => '🏷️',
            self::H5P => '🧩',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
