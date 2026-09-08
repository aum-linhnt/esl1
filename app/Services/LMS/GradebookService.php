<?php

namespace App\Services\LMS;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\ActivityCompletion;
use App\Models\UserProgress;
use App\Models\Enrollment;
use Illuminate\Support\Collection;

class GradebookService
{
    /**
     * Get the weighted average grade for a user in a course (0-100).
     */
    public function getCourseGrade(User $user, Course $course): float
    {
        $lessons = $course->lessons()->with('activities')->get();
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($lessons as $lesson) {
            $activityCount = $lesson->activities->count();
            if ($activityCount === 0) {
                continue;
            }

            $lessonGrade = $this->getLessonGrade($user, $lesson);
            $weight = $activityCount; // Weight by number of activities
            $totalWeight += $weight;
            $weightedSum += $lessonGrade * $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;
    }

    /**
     * Get grade for a specific lesson (average of activity scores).
     */
    public function getLessonGrade(User $user, Lesson $lesson): float
    {
        $completions = ActivityCompletion::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->get();

        if ($completions->isEmpty()) {
            // Fall back to user_progress score if no activity completions yet
            $progress = $user->getLessonProgress($lesson->id);
            return $progress ? (float) $progress->score : 0;
        }

        $totalScore = $completions->sum('score');
        $totalMaxScore = $completions->sum('max_score');

        return $totalMaxScore > 0
            ? round(($totalScore / $totalMaxScore) * 100, 2)
            : 0;
    }

    /**
     * Get detailed grades per lesson for a course.
     */
    public function getLessonGrades(User $user, Course $course): Collection
    {
        $lessons = $course->lessons()->with('activities')->get();

        return $lessons->map(function ($lesson) use ($user, $course) {
            $completions = ActivityCompletion::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->get();

            $progress = $user->getLessonProgress($lesson->id);
            $totalActivities = $lesson->activities->count();
            $completedActivities = $completions->count();
            $rawGrade = $this->getLessonGrade($user, $lesson);

            return [
                'lesson' => $lesson,
                'grade' => $rawGrade,
                'formatted' => $this->formatGradeByScale($rawGrade, $course->grading_scale, (float) $course->passing_grade),
                'completed_activities' => $completedActivities,
                'total_activities' => $totalActivities,
                'is_completed' => $progress ? $progress->completed : false,
                'time_spent' => $completions->sum('time_spent_seconds'),
                'progress_score' => $progress ? $progress->score : 0,
            ];
        });
    }

    /**
     * Get overall GPA across all enrolled courses.
     */
    public function getOverallGPA(User $user): float
    {
        $enrollments = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->with('course')
            ->get();

        if ($enrollments->isEmpty()) {
            return 0;
        }

        $totalGrade = 0;
        $courseCount = 0;

        foreach ($enrollments as $enrollment) {
            $grade = $this->getCourseGrade($user, $enrollment->course);
            if ($grade > 0) {
                $totalGrade += $grade;
                $courseCount++;
            }
        }

        return $courseCount > 0 ? round($totalGrade / $courseCount, 2) : 0;
    }

    /**
     * Get grade distribution for admin reports.
     */
    public function getGradeDistribution(): array
    {
        $enrollments = Enrollment::where('status', 'completed')
            ->whereNotNull('final_grade')
            ->get();

        $distribution = [
            'A' => 0, // 90-100
            'B' => 0, // 80-89
            'C' => 0, // 70-79
            'D' => 0, // 60-69
            'F' => 0, // < 60
        ];

        foreach ($enrollments as $enrollment) {
            $grade = $enrollment->final_grade;
            if ($grade >= 90) $distribution['A']++;
            elseif ($grade >= 80) $distribution['B']++;
            elseif ($grade >= 70) $distribution['C']++;
            elseif ($grade >= 60) $distribution['D']++;
            else $distribution['F']++;
        }

        return $distribution;
    }

    // ─────────────────────────────────────────────────────────────────
    //  MULTI-GRADING SCALE CONVERSION ENGINE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Convert a raw 0-100 score into a formatted grade object based on the grading scale.
     *
     * @param float  $rawScore     The raw score between 0 and 100
     * @param string $scale        The grading scale code (scale_100, scale_10, scale_4, scale_ielts, scale_pass_fail)
     * @param float  $passingGrade The passing grade threshold (0-100, default 50)
     * @return array{
     *     raw_score: float,
     *     score_display: string,
     *     score_suffix: string,
     *     grade_letter: string,
     *     rank_label: string,
     *     badge_color: string,
     *     is_passed: bool,
     *     scale_code: string,
     *     scale_name: string,
     *     scale_icon: string,
     * }
     */
    public function formatGradeByScale(float $rawScore, string $scale = 'scale_100', float $passingGrade = 50.0): array
    {
        $isPassed = $rawScore >= $passingGrade;
        $scaleMeta = Course::$gradingScales[$scale] ?? Course::$gradingScales[Course::SCALE_100];

        $base = [
            'raw_score'   => round($rawScore, 2),
            'is_passed'   => $isPassed,
            'scale_code'  => $scale,
            'scale_name'  => $scaleMeta['name'],
            'scale_icon'  => $scaleMeta['icon'],
        ];

        return match ($scale) {
            Course::SCALE_10        => array_merge($base, $this->convertToScale10($rawScore)),
            Course::SCALE_4         => array_merge($base, $this->convertToScale4($rawScore)),
            Course::SCALE_IELTS     => array_merge($base, $this->convertToScaleIELTS($rawScore)),
            Course::SCALE_PASS_FAIL => array_merge($base, $this->convertToPassFail($rawScore, $passingGrade)),
            default                 => array_merge($base, $this->convertToScale100($rawScore)),
        };
    }

    /**
     * Scale 100 (Percentage): 0% - 100%, Letter Grade A-F.
     */
    private function convertToScale100(float $raw): array
    {
        $display = number_format($raw, 1);

        if ($raw >= 90) return ['score_display' => $display, 'score_suffix' => '%', 'grade_letter' => 'A',  'rank_label' => 'Xuất sắc',    'badge_color' => 'emerald'];
        if ($raw >= 80) return ['score_display' => $display, 'score_suffix' => '%', 'grade_letter' => 'B',  'rank_label' => 'Giỏi',        'badge_color' => 'blue'];
        if ($raw >= 70) return ['score_display' => $display, 'score_suffix' => '%', 'grade_letter' => 'C',  'rank_label' => 'Khá',         'badge_color' => 'yellow'];
        if ($raw >= 60) return ['score_display' => $display, 'score_suffix' => '%', 'grade_letter' => 'D',  'rank_label' => 'Trung bình',  'badge_color' => 'orange'];
        return                 ['score_display' => $display, 'score_suffix' => '%', 'grade_letter' => 'F',  'rank_label' => 'Yếu',         'badge_color' => 'red'];
    }

    /**
     * Scale 10 (Vietnam Education System): 0.0 - 10.0.
     */
    private function convertToScale10(float $raw): array
    {
        $score10 = round($raw / 10, 1);
        $display = number_format($score10, 1);

        if ($score10 >= 9.0)  return ['score_display' => $display, 'score_suffix' => '/10', 'grade_letter' => 'A+', 'rank_label' => 'Xuất sắc',    'badge_color' => 'emerald'];
        if ($score10 >= 8.0)  return ['score_display' => $display, 'score_suffix' => '/10', 'grade_letter' => 'A',  'rank_label' => 'Giỏi',        'badge_color' => 'blue'];
        if ($score10 >= 6.5)  return ['score_display' => $display, 'score_suffix' => '/10', 'grade_letter' => 'B',  'rank_label' => 'Khá',         'badge_color' => 'yellow'];
        if ($score10 >= 5.0)  return ['score_display' => $display, 'score_suffix' => '/10', 'grade_letter' => 'C',  'rank_label' => 'Trung bình',  'badge_color' => 'orange'];
        if ($score10 >= 3.5)  return ['score_display' => $display, 'score_suffix' => '/10', 'grade_letter' => 'D',  'rank_label' => 'Yếu',         'badge_color' => 'red'];
        return                       ['score_display' => $display, 'score_suffix' => '/10', 'grade_letter' => 'F',  'rank_label' => 'Kém',         'badge_color' => 'red'];
    }

    /**
     * Scale 4.0 (US GPA System): 0.0 - 4.0 with letter grades.
     */
    private function convertToScale4(float $raw): array
    {
        // Standard US GPA conversion
        if ($raw >= 97) { $gpa = 4.0; $letter = 'A+'; $rank = 'Xuất sắc';         $color = 'emerald'; }
        elseif ($raw >= 93) { $gpa = 4.0; $letter = 'A';  $rank = 'Xuất sắc';     $color = 'emerald'; }
        elseif ($raw >= 90) { $gpa = 3.7; $letter = 'A-'; $rank = 'Giỏi';         $color = 'teal'; }
        elseif ($raw >= 87) { $gpa = 3.3; $letter = 'B+'; $rank = 'Giỏi';         $color = 'blue'; }
        elseif ($raw >= 83) { $gpa = 3.0; $letter = 'B';  $rank = 'Khá giỏi';     $color = 'blue'; }
        elseif ($raw >= 80) { $gpa = 2.7; $letter = 'B-'; $rank = 'Khá';          $color = 'sky'; }
        elseif ($raw >= 77) { $gpa = 2.3; $letter = 'C+'; $rank = 'Khá';          $color = 'yellow'; }
        elseif ($raw >= 73) { $gpa = 2.0; $letter = 'C';  $rank = 'Trung bình';   $color = 'yellow'; }
        elseif ($raw >= 70) { $gpa = 1.7; $letter = 'C-'; $rank = 'Trung bình';   $color = 'orange'; }
        elseif ($raw >= 67) { $gpa = 1.3; $letter = 'D+'; $rank = 'Dưới TB';      $color = 'orange'; }
        elseif ($raw >= 60) { $gpa = 1.0; $letter = 'D';  $rank = 'Yếu';          $color = 'red'; }
        else                { $gpa = 0.0; $letter = 'F';  $rank = 'Không đạt';    $color = 'red'; }

        return [
            'score_display' => number_format($gpa, 1),
            'score_suffix'  => '/4.0',
            'grade_letter'  => $letter,
            'rank_label'    => $rank,
            'badge_color'   => $color,
        ];
    }

    /**
     * Scale IELTS (International English Language Testing System): Band 1.0 - 9.0.
     */
    private function convertToScaleIELTS(float $raw): array
    {
        // Map 0-100 to Band 1.0 - 9.0 with 0.5 increments
        $bandRaw = 1 + ($raw / 100) * 8;
        $band = round($bandRaw * 2) / 2; // Round to nearest 0.5
        $band = max(1.0, min(9.0, $band));

        if ($band >= 8.5) { $rank = 'Expert User';           $color = 'emerald'; }
        elseif ($band >= 7.5) { $rank = 'Very Good User';    $color = 'teal'; }
        elseif ($band >= 6.5) { $rank = 'Good User';         $color = 'blue'; }
        elseif ($band >= 5.5) { $rank = 'Competent User';    $color = 'yellow'; }
        elseif ($band >= 4.5) { $rank = 'Modest User';       $color = 'orange'; }
        elseif ($band >= 3.5) { $rank = 'Limited User';      $color = 'red'; }
        else                  { $rank = 'Extremely Limited';  $color = 'red'; }

        return [
            'score_display' => number_format($band, 1),
            'score_suffix'  => '',
            'grade_letter'  => 'Band ' . number_format($band, 1),
            'rank_label'    => $rank,
            'badge_color'   => $color,
        ];
    }

    /**
     * Scale Pass/Fail (Competency-Based Assessment).
     */
    private function convertToPassFail(float $raw, float $passingGrade): array
    {
        $passed = $raw >= $passingGrade;

        return [
            'score_display' => $passed ? 'ĐẠT' : 'CHƯA ĐẠT',
            'score_suffix'  => '',
            'grade_letter'  => $passed ? 'P' : 'F',
            'rank_label'    => $passed ? 'Hoàn thành yêu cầu' : 'Chưa đạt yêu cầu',
            'badge_color'   => $passed ? 'emerald' : 'red',
        ];
    }
}
