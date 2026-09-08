<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QuestionBank;
use App\Services\Assessment\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModernQuestionTypesTest extends TestCase
{
    use RefreshDatabase;

    protected AssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->service = app(AssessmentService::class);
    }

    public function test_grading_evaluates_single_choice_mcq(): void
    {
        $q = QuestionBank::where('question_type', 'mcq')->first();
        $this->assertNotNull($q);

        $this->assertTrue($this->service->evaluateAnswer($q, $q->correct_answer));
        $this->assertFalse($this->service->evaluateAnswer($q, 'Wrong Answer Random'));
    }

    public function test_grading_evaluates_multiple_select(): void
    {
        $q = QuestionBank::where('question_type', 'multiple_select')->first();
        $this->assertNotNull($q);

        $correctArr = json_decode($q->correct_answer, true);
        $this->assertTrue($this->service->evaluateAnswer($q, $correctArr));
        $this->assertTrue($this->service->evaluateAnswer($q, array_reverse($correctArr))); // Order insensitive
        $this->assertFalse($this->service->evaluateAnswer($q, ['Fit', 'WrongOption']));
    }

    public function test_grading_evaluates_fill_blank(): void
    {
        $q = QuestionBank::where('question_type', 'fill_blank')->first();
        $this->assertNotNull($q);

        $this->assertTrue($this->service->evaluateAnswer($q, 'bought'));
        $this->assertTrue($this->service->evaluateAnswer($q, '  BOUGHT ')); // Trim & Case insensitive
        $this->assertFalse($this->service->evaluateAnswer($q, 'buyed'));
    }

    public function test_grading_evaluates_word_ordering(): void
    {
        $q = QuestionBank::where('question_type', 'word_ordering')->first();
        $this->assertNotNull($q);

        $this->assertTrue($this->service->evaluateAnswer($q, 'I study English every day'));
        $this->assertTrue($this->service->evaluateAnswer($q, ['I', 'study', 'English', 'every', 'day']));
        $this->assertFalse($this->service->evaluateAnswer($q, 'I English study every day'));
    }

    public function test_grading_evaluates_matching_pairs(): void
    {
        $q = QuestionBank::where('question_type', 'matching')->first();
        $this->assertNotNull($q);

        $correctPairs = json_decode($q->correct_answer, true);
        $this->assertTrue($this->service->evaluateAnswer($q, $correctPairs));
        
        $wrongPairs = $correctPairs;
        $wrongPairs['Ecosystem'] = 'Sai nghĩa';
        $this->assertFalse($this->service->evaluateAnswer($q, $wrongPairs));
    }

    public function test_grading_evaluates_true_false(): void
    {
        $q = QuestionBank::where('question_type', 'true_false')->first();
        $this->assertNotNull($q);

        $this->assertTrue($this->service->evaluateAnswer($q, 'True'));
        $this->assertTrue($this->service->evaluateAnswer($q, 'true'));
        $this->assertFalse($this->service->evaluateAnswer($q, 'False'));
    }

    public function test_grading_evaluates_audio_listening(): void
    {
        $q = QuestionBank::where('question_type', 'audio_listening')->first();
        $this->assertNotNull($q);

        $this->assertTrue($this->service->evaluateAnswer($q, 'Gate 14'));
        $this->assertFalse($this->service->evaluateAnswer($q, 'Gate 4'));
    }

    public function test_grading_evaluates_pronunciation_speech(): void
    {
        $q = QuestionBank::where('question_type', 'pronunciation_speech')->first();
        $this->assertNotNull($q);

        $this->assertTrue($this->service->evaluateAnswer($q, 'good morning nice to meet you'));
        $this->assertTrue($this->service->evaluateAnswer($q, 'Good morning, nice to meet you!'));
        $this->assertFalse($this->service->evaluateAnswer($q, 'Hello completely wrong phrase'));
    }
}
