<?php

namespace App\Services\Assessment;

use App\Models\User;
use App\Models\QuestionBank;
use App\Models\AssessmentSubmission;
use App\Models\LearnerSkill;
use App\Models\UserProgress;
use App\Services\AI\AiWritingService;
use App\Services\AI\AiSpeakingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssessmentService
{
    protected ?AiWritingService $aiWritingService;
    protected ?AiSpeakingService $aiSpeakingService;

    public function __construct(
        ?AiWritingService $aiWritingService = null,
        ?AiSpeakingService $aiSpeakingService = null
    ) {
        $this->aiWritingService = $aiWritingService ?? app(AiWritingService::class);
        $this->aiSpeakingService = $aiSpeakingService ?? app(AiSpeakingService::class);
    }

    /**
     * Grade submitted answers against the question bank, reward coins, and update skill matrix.
     * Supports 8 modern digital question types.
     *
     * @param int $userId
     * @param int|null $lessonId
     * @param array $submittedAnswers Key-value pair of [question_id => user_answer]
     * @param string $testType
     * @param mixed $questions Optional collection/array of QuestionBank models to enforce exact question order and total count
     * @return array
     */
    public function gradeSubmission(int $userId, ?int $lessonId, array $submittedAnswers, string $testType = 'lesson_quiz', $questions = null, array $meta = []): array
    {
        $user = User::findOrFail($userId);
        $details = [];
        $skillScores = []; // skill => ['correct' => 0, 'total' => 0]
        $correctCount = 0;

        // If explicit questions list is passed, use it to ensure exact order and count
        if ($questions && count($questions) > 0) {
            $questionList = $questions;
        } else {
            $qIds = array_keys($submittedAnswers);
            $questionList = QuestionBank::whereIn('id', $qIds)->get();
        }

        $totalQuestions = count($questionList);

        foreach ($questionList as $question) {
            $questionId = $question->id;
            $userAnswer = $submittedAnswers[$questionId] ?? null;

            $skill = $this->normalizeSkillName($question->skill);
            if (!isset($skillScores[$skill])) {
                $skillScores[$skill] = ['correct' => 0, 'total' => 0];
            }
            $skillScores[$skill]['total']++;

            $isAnswered = ($userAnswer !== null && $userAnswer !== '' && (!is_array($userAnswer) || count($userAnswer) > 0));
            $aiEvaluation = null;
            $aiType = null;

            $isSpeaking = ($skill === 'speaking')
                || in_array($question->question_type, ['speaking', 'pronunciation_speech', 'audio_recording'])
                || (is_string($userAnswer) && (str_starts_with($userAnswer, 'audio_recorded_') || str_starts_with($userAnswer, 'data:audio')));
            $isWriting = ($skill === 'writing') || in_array($question->question_type, ['essay', 'writing']);

            if ($isWriting && $isAnswered) {
                $aiType = 'writing';
                try {
                    $essayText = is_array($userAnswer) ? json_encode($userAnswer, JSON_UNESCAPED_UNICODE) : (string)$userAnswer;
                    $topic = $question->question_text ?: 'Writing Task';
                    $targetLevel = $question->difficulty ?: 'B1';

                    if (str_word_count(strip_tags($essayText)) >= 2) {
                        $aiEvaluation = $this->aiWritingService->evaluateEssay($essayText, $topic, $targetLevel);
                        $overallScore = (int)($aiEvaluation['overall_score'] ?? 75);
                        $isCorrect = $overallScore >= 60;
                    } else {
                        $isCorrect = false;
                    }
                } catch (\Throwable $e) {
                    Log::warning('AI Writing evaluation error in assessment: ' . $e->getMessage());
                    $isCorrect = true;
                }
            } elseif ($isSpeaking && $isAnswered) {
                $aiType = 'speaking';
                try {
                    $uAnsStr = is_array($userAnswer) ? json_encode($userAnswer, JSON_UNESCAPED_UNICODE) : (string)$userAnswer;
                    $audioBase64 = $meta['audio_recordings'][$questionId] ?? ($meta['audio_recordings'][(string)$questionId] ?? null);
                    $targetText = (string)($question->correct_answer ?: $question->question_text);

                    if (!empty($audioBase64) && str_starts_with($audioBase64, 'data:audio')) {
                        $aiEvaluation = $this->aiSpeakingService->assessBase64($audioBase64, $targetText);
                    } elseif (str_starts_with($uAnsStr, 'data:audio')) {
                        $aiEvaluation = $this->aiSpeakingService->assessBase64($uAnsStr, $targetText);
                    } elseif (filter_var($uAnsStr, FILTER_VALIDATE_URL)) {
                        $aiEvaluation = $this->aiSpeakingService->assessAudioUrl($uAnsStr, $targetText);
                    } else {
                        $spokenText = (!str_starts_with($uAnsStr, 'audio_recorded_') && !empty($uAnsStr)) ? $uAnsStr : $targetText;
                        $aiEvaluation = $this->aiSpeakingService->evaluatePronunciation($spokenText, $targetText);
                    }

                    $speakingScore = (float)($aiEvaluation['score'] ?? 80.0);
                    $isCorrect = $speakingScore >= 60.0;
                } catch (\Throwable $e) {
                    Log::warning('AI Speaking evaluation error in assessment: ' . $e->getMessage());
                    $isCorrect = true;
                }
            } else {
                $isCorrect = $isAnswered ? $this->evaluateAnswer($question, $userAnswer) : false;
            }

            if ($isCorrect) {
                $correctCount++;
                $skillScores[$skill]['correct']++;
            }

            $details[] = [
                'question_id' => $question->id,
                'version' => $question->version ?? 1,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $question->options ?? [],
                'skill' => $skill,
                'difficulty' => $question->difficulty,
                'user_answer' => is_array($userAnswer) ? json_encode($userAnswer, JSON_UNESCAPED_UNICODE) : (string)($userAnswer ?? ''),
                'correct_answer' => is_array($question->correct_answer) ? json_encode($question->correct_answer, JSON_UNESCAPED_UNICODE) : (string)$question->correct_answer,
                'is_correct' => $isCorrect,
                'is_answered' => $isAnswered,
                'explanation' => $question->explanation,
                'ai_evaluation' => $aiEvaluation,
                'ai_type' => $aiType,
            ];
        }

        $accuracyRate = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;
        $maxScore = $totalQuestions * 10;
        $totalScore = $correctCount * 10;
        $isPassed = $accuracyRate >= 60.0;

        // Reward XP & Coins & update learning streak based on performance
        $coinsEarned = 0;
        $xpEarned = 0;
        if ($accuracyRate == 100.0) {
            $coinsEarned = 10;
            $xpEarned = 50;
        } elseif ($accuracyRate >= 80.0) {
            $coinsEarned = 5;
            $xpEarned = 30;
        } elseif ($accuracyRate >= 60.0) {
            $coinsEarned = 2;
            $xpEarned = 15;
        } else {
            $xpEarned = 5; // Participation XP
        }

        if ($xpEarned > 0) {
            $user->increment('xp', $xpEarned);
        }
        if ($coinsEarned > 0) {
            $user->increment('coins', $coinsEarned);
        }

        // Record Daily Learning Activity to maintain/increment Streak 🔥
        $streakInfo = $user->recordDailyStudy();

        $timeSpent = (int) ($meta['time_spent_seconds'] ?? 0);
        $startedAt = !empty($meta['started_at']) ? Carbon::parse($meta['started_at']) : Carbon::now()->subSeconds($timeSpent);
        $attemptNumber = AssessmentSubmission::where('user_id', $userId)
            ->where('test_type', $testType)
            ->count() + 1;

        // Save submission log
        $submission = AssessmentSubmission::create([
            'user_id' => $userId,
            'lesson_id' => $lessonId,
            'test_type' => $testType,
            'attempt_number' => $attemptNumber,
            'time_spent_seconds' => $timeSpent,
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => Carbon::now(),
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'accuracy_rate' => $accuracyRate,
            'is_passed' => $isPassed,
            'answers_payload' => $details,
        ]);

        // Update lesson progress if associated with a lesson
        if ($lessonId) {
            UserProgress::updateOrCreate(
                ['user_id' => $userId, 'lesson_id' => $lessonId],
                [
                    'score' => max($totalScore, (UserProgress::where('user_id', $userId)->where('lesson_id', $lessonId)->value('score') ?? 0)),
                    'completed' => $isPassed,
                    'completed_at' => $isPassed ? Carbon::now() : null,
                ]
            );
        }

        // Update Diagnostic Matrix (learner_skills)
        $this->updateLearnerSkills($userId, $skillScores);

        return [
            'submission_id' => $submission->id,
            'attempt_number' => $attemptNumber,
            'time_spent_seconds' => $timeSpent,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'total_score' => $totalScore,
            'max_score' => $maxScore,
            'accuracy_rate' => $accuracyRate,
            'is_passed' => $isPassed,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned ?? (int) ceil($xpEarned / 2),
            'current_xp' => $user->fresh()->xp,
            'current_coins' => $user->fresh()->coins,
            'streak_info' => $streakInfo,
            'details' => $details,
            'skill_breakdown' => $skillScores,
        ];
    }

    /**
     * Universal Evaluator supporting all modern question types via QuestionTypeManager.
     */
    public function evaluateAnswer(QuestionBank $question, mixed $userAnswer): bool
    {
        $type = $question->question_type ?? 'mcq';
        $handler = \App\QuestionTypes\QuestionTypeManager::get($type);

        return $handler->evaluate($question, $userAnswer);
    }



    /**
     * Update or create learner skill records based on recent assessment accuracy.
     */
    public function updateLearnerSkills(int $userId, array $skillScores): void
    {
        foreach ($skillScores as $skill => $data) {
            if ($data['total'] === 0) continue;

            $newScore = round(($data['correct'] / $data['total']) * 100);

            $existing = LearnerSkill::where('user_id', $userId)->where('skill_type', $skill)->first();

            if ($existing) {
                // Exponential moving average for skill mastery
                $blendedScore = round(($existing->mastery_score * 0.4) + ($newScore * 0.6));
                $level = $this->determineLevelFromScore($blendedScore);

                $existing->update([
                    'mastery_score' => $blendedScore,
                    'assessed_level' => $level,
                    'last_assessed_at' => Carbon::now(),
                ]);
            } else {
                $level = $this->determineLevelFromScore($newScore);
                LearnerSkill::create([
                    'user_id' => $userId,
                    'skill_type' => $skill,
                    'mastery_score' => $newScore,
                    'assessed_level' => $level,
                    'last_assessed_at' => Carbon::now(),
                ]);
            }
        }
    }

    private function determineLevelFromScore(int $score): string
    {
        if ($score >= 85) return 'B2';
        if ($score >= 70) return 'B1';
        if ($score >= 50) return 'A2';
        return 'A1';
    }

    private function normalizeSkillName(string $skill): string
    {
        $map = [
            'listening' => 'listening',
            'audio_listening' => 'listening',
            'speaking' => 'speaking',
            'audio_recording' => 'speaking',
            'reading' => 'reading',
            'writing' => 'writing',
            'essay_writing' => 'writing',
            'vocab' => 'vocabulary',
            'vocabulary' => 'vocabulary',
            'grammar' => 'grammar',
        ];
        return $map[strtolower(trim($skill))] ?? strtolower(trim($skill));
    }
}
