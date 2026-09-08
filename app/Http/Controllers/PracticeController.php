<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Models\ExamSet;
use App\Models\AdaptiveTestSession;
use App\Models\AssessmentSubmission;
use App\Models\LearnerSkill;
use App\Models\Course;
use App\Services\Assessment\AssessmentService;
use Illuminate\Http\Request;

class PracticeController extends Controller
{
    protected AssessmentService $assessmentService;

    public function __construct(
        AssessmentService $assessmentService
    ) {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Practice hub view: 4-Skill Selection + Full Mock 4-Skill Exam Sets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $skill = $request->get('skill', 'adaptive'); // Default to Adaptive CAT Testing

        // User diagnostic skill matrix
        $userSkills = LearnerSkill::where('user_id', $user->id)->get()->keyBy('skill_type');

        $examCounts = ExamSet::where('is_published', true)
            ->selectRaw('skill, count(*) as total')
            ->groupBy('skill')
            ->pluck('total', 'skill');

        // Skills metadata & stats (Adaptive CAT + Full Mock + 4 Standard Skills)
        $skillsOverview = [
            'adaptive' => [
                'key' => 'adaptive',
                'title' => 'Thi Thích Ứng AI (CAT)',
                'subtitle' => '4-Skill Computerized Adaptive',
                'icon' => '⚡',
                'color' => 'from-violet-500 to-fuchsia-600',
                'gradient' => 'from-violet-950/40 via-purple-950/20 to-slate-900/80',
                'border_active' => 'border-violet-500 shadow-violet-500/20',
                'badge_color' => 'bg-violet-500/20 text-violet-300 border-violet-500/30',
                'user_level' => $userSkills->get('adaptive')?->cefr_level ?? 'B1',
                'mastery_score' => $userSkills->get('adaptive')?->score ?? 70,
                'exam_count' => $examCounts['adaptive'] ?? 6,
                'description' => 'Bài thi thích ứng AI tự động điều chỉnh độ khó theo năng lực',
                'cefr_level' => $userSkills->get('adaptive')?->cefr_level ?? 'Chưa thi',
                'score' => $userSkills->get('adaptive')?->score,
            ],
            'full_mock' => [
                'key' => 'full_mock',
                'title' => 'Đề Thi 4 Kỹ Năng',
                'subtitle' => 'Listening + Reading + Writing + Speaking',
                'icon' => '📝',
                'color' => 'from-sky-500 to-blue-600',
                'gradient' => 'from-sky-950/40 via-blue-950/20 to-slate-900/80',
                'border_active' => 'border-sky-500 shadow-sky-500/20',
                'badge_color' => 'bg-sky-500/20 text-sky-300 border-sky-500/30',
                'user_level' => $userSkills->get('full_mock')?->cefr_level ?? 'B2',
                'mastery_score' => $userSkills->get('full_mock')?->score ?? 65,
                'exam_count' => $examCounts['full_mock'] ?? 4,
                'description' => 'Đề thi mô phỏng đầy đủ 4 phần như thi thật',
                'cefr_level' => $userSkills->get('full_mock')?->cefr_level ?? '—',
                'score' => $userSkills->get('full_mock')?->score,
            ],
            'listening' => [
                'key' => 'listening',
                'title' => 'Nghe (Listening)',
                'subtitle' => 'Audio Comprehension',
                'icon' => '🎧',
                'color' => 'from-emerald-500 to-teal-600',
                'gradient' => 'from-emerald-950/40 via-teal-950/20 to-slate-900/80',
                'border_active' => 'border-emerald-500 shadow-emerald-500/20',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                'user_level' => $userSkills->get('listening')?->cefr_level ?? 'B1',
                'mastery_score' => $userSkills->get('listening')?->score ?? 60,
                'exam_count' => $examCounts['listening'] ?? 8,
                'description' => 'Nghe hiểu hội thoại, bài giảng, thời sự',
                'cefr_level' => $userSkills->get('listening')?->cefr_level ?? '—',
                'score' => $userSkills->get('listening')?->score,
            ],
            'reading' => [
                'key' => 'reading',
                'title' => 'Đọc (Reading)',
                'subtitle' => 'Reading Comprehension',
                'icon' => '📖',
                'color' => 'from-purple-500 to-indigo-600',
                'gradient' => 'from-purple-950/40 via-indigo-950/20 to-slate-900/80',
                'border_active' => 'border-purple-500 shadow-purple-500/20',
                'badge_color' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                'user_level' => $userSkills->get('reading')?->cefr_level ?? 'B2',
                'mastery_score' => $userSkills->get('reading')?->score ?? 75,
                'exam_count' => $examCounts['reading'] ?? 10,
                'description' => 'Đọc hiểu đoạn văn, tin tức, bài báo',
                'cefr_level' => $userSkills->get('reading')?->cefr_level ?? '—',
                'score' => $userSkills->get('reading')?->score,
            ],
            'writing' => [
                'key' => 'writing',
                'title' => 'Viết (Writing)',
                'subtitle' => 'Essay & Academic Writing',
                'icon' => '✍️',
                'color' => 'from-cyan-500 to-blue-600',
                'gradient' => 'from-cyan-950/40 via-blue-950/20 to-slate-900/80',
                'border_active' => 'border-cyan-500 shadow-cyan-500/20',
                'badge_color' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                'user_level' => $userSkills->get('writing')?->cefr_level ?? 'B1',
                'mastery_score' => $userSkills->get('writing')?->score ?? 55,
                'exam_count' => $examCounts['writing'] ?? 5,
                'description' => 'Viết bài luận, email, báo cáo',
                'cefr_level' => $userSkills->get('writing')?->cefr_level ?? '—',
                'score' => $userSkills->get('writing')?->score,
            ],
            'speaking' => [
                'key' => 'speaking',
                'title' => 'Nói (Speaking)',
                'subtitle' => 'Pronunciation & Fluency',
                'icon' => '🎙️',
                'color' => 'from-amber-500 to-orange-600',
                'gradient' => 'from-amber-950/40 via-orange-950/20 to-slate-900/80',
                'border_active' => 'border-amber-500 shadow-amber-500/20',
                'badge_color' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                'user_level' => $userSkills->get('speaking')?->cefr_level ?? 'A2',
                'mastery_score' => $userSkills->get('speaking')?->score ?? 50,
                'exam_count' => $examCounts['speaking'] ?? 5,
                'description' => 'Luyện phát âm, đối thoại, thuyết trình',
                'cefr_level' => $userSkills->get('speaking')?->cefr_level ?? '—',
                'score' => $userSkills->get('speaking')?->score,
            ],
        ];

        // Get exam sets for selected skill tab
        $examSets = $this->getExamSetsForSkill($skill, $user->id);

        $lastSession = AdaptiveTestSession::where('user_id', $user->id)->latest()->first();

        return view('practice.index', [
            'user' => $user,
            'skill' => $skill,
            'currentSkill' => $skill,
            'skillsOverview' => $skillsOverview,
            'examSets' => $examSets,
            'lastSession' => $lastSession,
        ]);
    }

    /**
     * Show an exam set (static) for practice.
     */
    public function showExam(Request $request, string $testKey)
    {
        $user = $request->user();
        $exam = $this->getExamDefinition($testKey);

        if (!$exam) {
            return redirect()->route('practice.index')->with('error', 'Đề thi không tồn tại hoặc chưa xuất bản.');
        }

        // Fetch questions for this exam
        $questions = $this->getQuestionsForExam($exam);

        // Fetch user previous attempts
        $previousAttempts = AssessmentSubmission::where('user_id', $user->id)
            ->where('test_type', $testKey)
            ->orderBy('attempt_number', 'asc')
            ->get();

        return view('practice.exam', [
            'exam' => $exam,
            'questions' => $questions,
            'previousAttempts' => $previousAttempts,
            'user' => $user,
        ]);
    }

    /**
     * Submit answers for a specific exam set and show scorecard.
     */
    public function submitExam(Request $request, string $testKey)
    {
        $user = $request->user();
        $exam = $this->getExamDefinition($testKey);

        if (!$exam) {
            return redirect()->route('practice.index')->with('error', 'Đề thi không tồn tại.');
        }

        $submittedAnswers = $request->input('answers', []);
        $audioRecordings = $request->input('audio_recordings', []);
        $timeSpent = (int) $request->input('time_spent_seconds', 0);
        $startedAt = $request->input('started_at');
        $questions = $this->getQuestionsForExam($exam);

        $result = $this->assessmentService->gradeSubmission(
            $user->id,
            null,
            $submittedAnswers,
            $testKey,
            $questions,
            [
                'time_spent_seconds' => $timeSpent,
                'started_at' => $startedAt,
                'audio_recordings' => $audioRecordings,
            ]
        );

        return view('practice.scorecard', [
            'exam' => $exam,
            'result' => $result,
            'user' => $user,
        ]);
    }

    /**
     * Review a specific past attempt scorecard.
     */
    public function reviewAttempt(Request $request, string $testKey, int $submissionId)
    {
        $user = $request->user();
        $exam = $this->getExamDefinition($testKey);
        if (!$exam) {
            return redirect()->route('practice.index')->with('error', 'Đề thi không tồn tại.');
        }

        $submission = AssessmentSubmission::where('id', $submissionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $details = $submission->answers_payload ?? [];
        $modified = false;
        foreach ($details as &$d) {
            $uAns = (string)($d['user_answer'] ?? '');
            $skill = strtolower(trim($d['skill'] ?? ''));
            $isSpeaking = ($skill === 'speaking')
                || in_array($d['question_type'] ?? '', ['speaking', 'pronunciation_speech', 'audio_recording'])
                || str_starts_with($uAns, 'audio_recorded_')
                || str_starts_with($uAns, 'data:audio');
            $isWriting = ($skill === 'writing') || in_array($d['question_type'] ?? '', ['essay', 'writing']);

            if ($isSpeaking && !empty($uAns) && empty($d['is_correct'])) {
                $d['is_correct'] = true;
                $modified = true;
            }

            // Backfill AI evaluations if missing
            if ($isSpeaking && empty($d['ai_evaluation']) && !empty($uAns)) {
                $d['ai_type'] = 'speaking';
                try {
                    $targetText = (string)($d['correct_answer'] ?? $d['question_text'] ?? 'English Speaking');
                    $speakingService = app(\App\Services\AI\AiSpeakingService::class);
                    if (str_starts_with($uAns, 'data:audio')) {
                        $d['ai_evaluation'] = $speakingService->assessBase64($uAns, $targetText);
                    } else {
                        $d['ai_evaluation'] = $speakingService->evaluatePronunciation($targetText, $targetText);
                    }
                    $modified = true;
                } catch (\Throwable $e) {}
            } elseif ($isWriting && empty($d['ai_evaluation']) && !empty($uAns)) {
                $d['ai_type'] = 'writing';
                try {
                    $writingService = app(\App\Services\AI\AiWritingService::class);
                    $d['ai_evaluation'] = $writingService->evaluateEssay($uAns, (string)($d['question_text'] ?? 'Essay'), (string)($d['difficulty'] ?? 'B1'));
                    $modified = true;
                } catch (\Throwable $e) {}
            }
        }
        unset($d);

        if ($modified) {
            $correctCount = collect($details)->where('is_correct', true)->count();
            $totalQuestions = count($details);
            $accuracyRate = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;
            $submission->answers_payload = $details;
            $submission->accuracy_rate = $accuracyRate;
            $submission->is_passed = $accuracyRate >= 60.0;
            $submission->total_score = $correctCount * 10;
            $submission->save();
        }

        $totalQuestions = count($details);
        $correctCount = collect($details)->where('is_correct', true)->count();

        $skillScores = [];
        foreach ($details as $d) {
            $s = $d['skill'] ?? 'general';
            if (!isset($skillScores[$s])) {
                $skillScores[$s] = ['total' => 0, 'correct' => 0];
            }
            $skillScores[$s]['total']++;
            if (!empty($d['is_correct'])) {
                $skillScores[$s]['correct']++;
            }
        }

        $xpEarned = $submission->total_score ?? ($correctCount * 10);
        $coinsEarned = (int) ceil($xpEarned / 2);

        $result = [
            'submission_id' => $submission->id,
            'attempt_number' => $submission->attempt_number,
            'time_spent_seconds' => $submission->time_spent_seconds,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'total_score' => $submission->total_score,
            'max_score' => $submission->max_score,
            'accuracy_rate' => $submission->accuracy_rate,
            'is_passed' => $submission->is_passed,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'skill_breakdown' => $skillScores,
            'details' => $details,
            'is_review' => true,
        ];

        return view('practice.scorecard', [
            'exam' => $exam,
            'result' => $result,
            'user' => $user,
            'submission' => $submission,
            'is_review' => true,
        ]);
    }

    // ─── HELPER METHODS FOR EXAM SETS (DATABASE BACKED) ───

    private function getExamSetsForSkill(string $skill, int $userId): array
    {
        $dbExams = ExamSet::where('skill', $skill)
            ->where('is_published', true)
            ->orderBy('id', 'asc')
            ->get();

        $submissions = AssessmentSubmission::where('user_id', $userId)->get();
        $latestAdaptiveSession = AdaptiveTestSession::where('user_id', $userId)
            ->where('status', 'finished')
            ->latest()
            ->first();

        $results = [];
        foreach ($dbExams as $exam) {
            $userSub = $submissions->where('test_type', $exam->key)->sortByDesc('accuracy_rate')->first();

            $isCompleted = $userSub !== null;
            $highestAccuracy = $userSub ? $userSub->accuracy_rate : null;
            $isPassed = $userSub ? $userSub->is_passed : false;

            if (!$isCompleted && $exam->skill === 'adaptive' && $latestAdaptiveSession) {
                $isCompleted = true;
                $highestAccuracy = round(($latestAdaptiveSession->correct_count / max(1, $latestAdaptiveSession->total_questions_answered)) * 100);
                $isPassed = $highestAccuracy >= 60;
            }

            $actionUrl = $exam->skill === 'adaptive'
                ? route('practice.adaptive.startByKey', $exam->key)
                : route('practice.exam', $exam->key);

            $results[] = [
                'id' => $exam->id,
                'key' => $exam->key,
                'title' => $exam->title,
                'difficulty' => $exam->difficulty,
                'skill' => $exam->skill,
                'question_count' => $exam->question_count,
                'duration_minutes' => $exam->duration_minutes,
                'reward_coins' => $exam->reward_coins,
                'description' => $exam->description,
                'sections' => $exam->sections,
                'question_ids' => $exam->question_ids,
                'is_completed' => $isCompleted,
                'highest_accuracy' => $highestAccuracy,
                'is_passed' => $isPassed,
                'action_url' => $actionUrl,
            ];
        }

        return $results;
    }

    private function getExamDefinition(string $testKey): ?array
    {
        $exam = ExamSet::where('key', $testKey)->where('is_published', true)->first();

        if (!$exam) {
            return null;
        }

        return [
            'id' => $exam->id,
            'key' => $exam->key,
            'title' => $exam->title,
            'difficulty' => $exam->difficulty,
            'skill' => $exam->skill,
            'question_count' => $exam->question_count,
            'duration_minutes' => $exam->duration_minutes,
            'reward_coins' => $exam->reward_coins,
            'description' => $exam->description,
            'sections' => $exam->sections,
            'question_ids' => $exam->question_ids,
        ];
    }

    private function getQuestionsForExam(array $exam)
    {
        // 1. If explicit Question IDs are set
        if (!empty($exam['question_ids']) && is_array($exam['question_ids'])) {
            return QuestionBank::whereIn('id', $exam['question_ids'])->get();
        }

        // 2. If exam has predefined sections (e.g. mock tests with vocabulary, grammar, reading, listening)
        if (!empty($exam['sections']) && is_array($exam['sections'])) {
            $collected = collect();
            foreach ($exam['sections'] as $secSkill => $secCount) {
                $qQuery = QuestionBank::where('skill', $secSkill);
                if (!empty($exam['difficulty'])) {
                    $qQuery->where('difficulty', $exam['difficulty']);
                }
                $questions = $qQuery->take($secCount)->get();
                if ($questions->count() < $secCount) {
                    $fallback = QuestionBank::where('skill', $secSkill)
                        ->whereNotIn('id', $questions->pluck('id'))
                        ->take($secCount - $questions->count())
                        ->get();
                    $questions = $questions->concat($fallback);
                }
                $collected = $collected->concat($questions);
            }
            if ($collected->isNotEmpty()) {
                return $collected;
            }
        }

        // 3. If it is a Full 4-Skill Comprehensive Mock Exam
        if ($exam['skill'] === 'full_mock') {
            $orderedSkills = ['listening', 'reading', 'writing', 'speaking'];
            $collected = collect();
            
            foreach ($orderedSkills as $sk) {
                $questions = QuestionBank::where('skill', $sk)
                    ->orderBy('id', 'asc')
                    ->get();
                $collected = $collected->concat($questions);
            }
            
            return $collected;
        }

        // 4. Single Skill Exam (Listening, Reading, Writing, Speaking, etc.)
        return QuestionBank::where('skill', $exam['skill'])
            ->orderBy('id', 'asc')
            ->take($exam['question_count'])
            ->get();
    }
}
