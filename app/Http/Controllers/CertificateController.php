<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    /**
     * View or issue a completion certificate for a course.
     */
    public function show(Request $request, int $courseId)
    {
        $user = $request->user();
        $course = Course::with('lessons')->findOrFail($courseId);

        // Find existing certificate or generate new one
        $certificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$certificate) {
            $code = 'ESL-' . date('Y') . '-' . strtoupper($course->level) . '-' . strtoupper(Str::random(6));
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'certificate_code' => $code,
                'level' => $course->level,
                'final_score' => 95,
                'issued_at' => now(),
            ]);
        }

        return view('certificates.show', [
            'certificate' => $certificate,
            'user' => $user,
            'course' => $course,
        ]);
    }
}
