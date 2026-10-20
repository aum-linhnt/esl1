<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TeacherTutorOverview
{
    public function courses(User $user)
    {
        if (! $user->isActive()) {
            return collect();
        }
        $query = Course::query()->orderBy('title');
        if (! $user->isAdmin()) {
            $managed = $user->enrollments()->whereIn('course_role', ['teacher', 'manager'])->get()
                ->filter(fn ($e) => $e->hasValidAccess())->pluck('course_id');
            $query->where(fn ($q) => $q->whereIn('id', $managed)
                ->when($user->isTeacher(), fn ($q) => $q->orWhere('created_by', $user->id)));
        }
        return $query->get();
    }

    private function messages(Course $course, Carbon $start, Carbon $end)
    {
        return DB::table('tutor_ai_conversation_messages as m')
            ->join('tutor_ai_conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.course_id', (string) $course->id)
            ->whereBetween('m.created_at', [$start->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'), $end->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s')]);
    }

    public function report(Course $course, int $days): array
    {
        $start = now()->timezone('Asia/Ho_Chi_Minh')->startOfDay()->subDays($days - 1);
        $end = now()->timezone('Asia/Ho_Chi_Minh');
        $ready = Schema::hasTable('tutor_ai_conversation_messages') && Schema::hasTable('tutor_ai_conversations');
        $daily = collect(range(0, $days - 1))->map(fn ($i) => ['date' => $start->copy()->addDays($i)->format('Y-m-d'), 'label' => $start->copy()->addDays($i)->format('d/m'), 'questions' => 0, 'costs' => [], 'unpriced' => 0])->keyBy('date')->all();
        $report = ['ready' => $ready, 'days' => $days, 'start' => $start, 'end' => $end, 'daily' => $daily,
            'questions' => 0, 'previousQuestions' => 0, 'helpful' => null, 'feedbackCount' => 0, 'previousHelpful' => null,
            'failed' => 0, 'errorSummary' => collect(), 'popular' => collect(), 'costs' => collect(), 'learners' => collect(),
            'lessons' => $course->lessons()->get(), 'questionCounts' => collect()];
        if ($ready) {
            $messages = $this->messages($course, $start, $end);
            $report['questions'] = (clone $messages)->count();
            $previousStart = $start->copy()->subDays($days);
            $previousEnd = $start->copy()->subSecond();
            $previous = $this->messages($course, $previousStart, $previousEnd);
            $report['previousQuestions'] = (clone $previous)->count();
            foreach ((clone $messages)->select('m.created_at')->cursor() as $message) {
                $day = Carbon::parse($message->created_at, config('app.timezone'))->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
                $daily[$day]['questions']++;
            }
            $report['failed'] = (clone $messages)->where('m.status', 'failed')->count();
            $report['errorSummary'] = (clone $messages)->where('m.status', 'failed')->select('m.error_code')
                ->selectRaw('COUNT(*) as total')->groupBy('m.error_code')->orderByDesc('total')->orderBy('m.error_code')->get()
                ->each(fn ($row) => $row->guide = TutorFailureGuide::describe($row->error_code));
            $report['popular'] = (clone $messages)->select('m.user_content')->selectRaw('COUNT(*) as total')
                ->groupBy('m.user_content')->orderByDesc('total')->orderBy('m.user_content')->limit(5)->get();
            $report['questionCounts'] = (clone $messages)->select('c.user_id')->selectRaw('COUNT(*) as total')->groupBy('c.user_id')->pluck('total', 'user_id');
            if (Schema::hasTable('tutor_ai_message_feedback')) {
                foreach (['current' => $messages, 'previous' => $previous] as $key => $period) {
                    $feedback = DB::table('tutor_ai_message_feedback')->whereIn('message_id', (clone $period)->select('m.id'))
                        ->whereIn('rating', ['helpful', 'unhelpful'])->selectRaw('COUNT(*) as total, SUM(CASE WHEN rating = ? THEN 1 ELSE 0 END) as helpful', ['helpful'])->first();
                    $rate = $feedback->total ? round(100 * $feedback->helpful / $feedback->total) : null;
                    if ($key === 'current') {
                        $report['helpful'] = $rate;
                        $report['feedbackCount'] = (int) $feedback->total;
                    } else {
                        $report['previousHelpful'] = $rate;
                    }
                }
            }
            if (Schema::hasTable('tutor_ai_usage_records')) {
                // Include tutor calls and their retrieval embeddings once; exclude unrelated Writing/Speaking calls.
                $usage = DB::table('tutor_ai_usage_records')->where('created_at', '<=', $end->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'))->where(function ($q) use ($messages) {
                    $q->whereIn('request_id', (clone $messages)->select('m.request_id'))
                        ->orWhereIn('request_id', (clone $messages)->select('m.embedding_request_id'));
                });
                $report['costs'] = (clone $usage)->selectRaw('currency, SUM(estimated_cost) as cost, COUNT(estimated_cost) as priced, COUNT(*) - COUNT(estimated_cost) as unpriced')->groupBy('currency')->get();
                foreach ((clone $usage)->select('created_at', 'currency', 'estimated_cost')->cursor() as $row) {
                    $day = Carbon::parse($row->created_at, config('app.timezone'))->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
                    if (! isset($daily[$day])) continue;
                    if ($row->estimated_cost === null) $daily[$day]['unpriced']++;
                    else $daily[$day]['costs'][$row->currency] = ($daily[$day]['costs'][$row->currency] ?? 0) + (float) $row->estimated_cost;
                }
            }
        }
        // Course grades are stored on a percentage scale; never infer weak topics from a chat count.
        $threshold = max(75, (float) ($course->passing_grade ?? 50));
        $report['learners'] = Enrollment::where('course_id', $course->id)->where('course_role', 'student')
            ->whereIn('status', ['active', 'completed'])->whereNotNull('final_grade')->where('final_grade', '<', $threshold)
            ->with('user')->orderBy('final_grade')->get()->filter(fn ($e) => $e->user !== null)->values();
        $report['daily'] = array_values($daily);
        return $report;
    }

    public function detail(Course $course, array $report, string $section, ?string $learnerId, ?string $errorCode = null, ?int $errorLessonId = null)
    {
        if ($section === 'learners') {
            return Enrollment::where('course_id', $course->id)->where('course_role', 'student')->whereIn('status', ['active', 'completed'])
                ->when($learnerId !== null, fn ($q) => $q->where('user_id', $learnerId))->with('user')->orderBy('final_grade')->paginate(20)->withQueryString();
        }
        if (! $report['ready']) return null;
        $history = $this->messages($course, $report['start'], $report['end'])
            ->when($section === 'errors', fn ($q) => $q->where('m.status', 'failed')->when($errorCode !== null, fn ($q) => $q->where('m.error_code', $errorCode))
                ->when($errorLessonId !== null, fn ($q) => $q->where('c.lesson_id', (string) $errorLessonId)))
            ->select('m.user_content', 'm.status', 'm.error_code', 'm.created_at', 'c.lesson_id')->orderByDesc('m.created_at')->orderBy('m.id')->paginate(20)->withQueryString();
        $history->getCollection()->each(function ($message) use ($report) {
            $message->lesson = $report['lessons']->first(fn ($lesson) => (string) $lesson->id === $message->lesson_id);
            $message->guide = TutorFailureGuide::describe($message->error_code);
        });
        return $history;
    }
}
