<?php

namespace App\QuestionTypes;

use App\QuestionTypes\Handlers\McqHandler;
use App\QuestionTypes\Handlers\MultipleSelectHandler;
use App\QuestionTypes\Handlers\FillBlankHandler;
use App\QuestionTypes\Handlers\WordOrderingHandler;
use App\QuestionTypes\Handlers\MatchingHandler;
use App\QuestionTypes\Handlers\TrueFalseHandler;
use App\QuestionTypes\Handlers\EssayWritingHandler;
use App\QuestionTypes\Handlers\SpeakingHandler;

class QuestionTypeManager
{
    /**
     * @var array<string, QuestionTypeInterface>
     */
    protected static array $handlers = [];

    /**
     * Boot and register all default question type handlers.
     */
    protected static function registerDefaults(): void
    {
        if (!empty(static::$handlers)) {
            return;
        }

        $mcq = new McqHandler();
        $multipleSelect = new MultipleSelectHandler();
        $fillBlank = new FillBlankHandler();
        $wordOrdering = new WordOrderingHandler();
        $matching = new MatchingHandler();
        $trueFalse = new TrueFalseHandler();
        $essay = new EssayWritingHandler();
        $speaking = new SpeakingHandler();

        // 1. MCQ and variations
        static::register('mcq', $mcq);
        static::register('multiple_choice', $mcq);
        static::register('audio_listening', $mcq);

        // 2. Multiple Select
        static::register('multiple_select', $multipleSelect);

        // 3. Fill Blank
        static::register('fill_blank', $fillBlank);

        // 4. Word Ordering & Drag Drop
        static::register('word_ordering', $wordOrdering);
        static::register('drag_drop', $wordOrdering);

        // 5. Matching Pairs
        static::register('matching', $matching);

        // 6. True / False
        static::register('true_false', $trueFalse);

        // 7. Writing & Essays
        static::register('essay_writing', $essay);
        static::register('writing', $essay);

        // 8. Speaking & Audio Recording
        static::register('pronunciation_speech', $speaking);
        static::register('audio_recording', $speaking);
        static::register('speaking', $speaking);
    }

    /**
     * Register a custom or extended question type handler.
     */
    public static function register(string $type, QuestionTypeInterface $handler): void
    {
        static::$handlers[$type] = $handler;
    }

    /**
     * Get the handler for a given question type.
     */
    public static function get(string $type): QuestionTypeInterface
    {
        static::registerDefaults();

        return static::$handlers[$type] ?? static::$handlers['mcq'];
    }

    /**
     * Get all registered question type handlers.
     *
     * @return array<string, QuestionTypeInterface>
     */
    public static function all(): array
    {
        static::registerDefaults();

        return static::$handlers;
    }
}
