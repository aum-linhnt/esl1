@php
    $policyErrors = $errors->getBag('tutorPolicy');
    $policyOptions = ['no_answer' => 'Không hỗ trợ đáp án', 'hints_only' => 'Chỉ gợi ý (cấp 1–3)', 'hints_first' => 'Gợi ý trước, lời giải ở cấp 4', 'full_solution' => 'Cho phép lời giải ngay', 'teacher_controlled' => 'Giảng viên quyết định lời giải'];
    $selectedPolicy = $policyErrors->any() ? old('ai_answer_policy', $policyLesson?->ai_answer_policy ?? 'hints_only') : ($policyLesson?->ai_answer_policy ?? 'hints_only');
    if (!is_string($selectedPolicy) || !array_key_exists($selectedPolicy, $policyOptions)) $selectedPolicy = $policyLesson?->ai_answer_policy ?? 'hints_only';
    $solutionAllowed = $policyErrors->any() ? in_array(old('ai_teacher_solution_allowed'), [true, 1, '1'], true) : (bool) $policyLesson?->ai_teacher_solution_allowed;
    $examMode = $policyErrors->any() ? in_array(old('ai_exam_mode'), [true, 1, '1'], true) : (bool) $policyLesson?->ai_exam_mode;
@endphp
@if(session('tutor-policy-saved'))<p class="tdash-policy-success" role="status">@include('dashboard-v2.icon', ['name' => 'check-circle', 'size' => 18]) {{ session('tutor-policy-saved') }}</p>@endif
@if($policyErrors->any())<div class="tdash-policy-errors" role="alert">@foreach($policyErrors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($policyLesson)
    <form method="get" class="tdash-lesson-picker" action="{{ route('teacher.ai-tutor.index') }}#course-config">
        <input type="hidden" name="course_id" value="{{ $course->id }}"><input type="hidden" name="days" value="{{ $days }}">
        <label for="tutor-policy-lesson">Bài học cần cấu hình</label>
        <div><select name="lesson_id" id="tutor-policy-lesson">@foreach($report['lessons'] as $lesson)<option value="{{ $lesson->id }}" @selected($lesson->id === $policyLesson->id)>{{ $lesson->title }}</option>@endforeach</select><button type="submit" class="tdash-outline">Chọn</button></div>
    </form>
    <details class="tdash-policy" @if($policyErrors->any() || session('tutor-policy-saved') || request()->filled('lesson_id')) open @endif>
        <summary>Chỉnh chính sách · {{ $policyLesson->title }}</summary>
        <form method="post" action="{{ route('teacher.ai-tutor.policy.update', [$course->id, $policyLesson->id]) }}" data-tutor-policy-form>
            @csrf @method('PUT')
            <input type="hidden" name="days" value="{{ $days }}">
            <label for="tutor-answer-policy">Hỗ trợ đáp án</label>
            <select name="ai_answer_policy" id="tutor-answer-policy">@foreach($policyOptions as $value => $label)<option value="{{ $value }}" @selected($selectedPolicy === $value)>{{ $label }}</option>@endforeach</select>
            <input type="hidden" name="ai_teacher_solution_allowed" value="0">
            <label class="tdash-toggle-row"><span><strong>Cho phép lời giải ở cấp 4</strong><small>Khi chọn “Giảng viên quyết định lời giải”.</small></span><input type="checkbox" role="switch" name="ai_teacher_solution_allowed" value="1" data-policy-solution @checked($solutionAllowed)><span class="tdash-switch-track" aria-hidden="true"></span></label>
            <input type="hidden" name="ai_exam_mode" value="0">
            <label class="tdash-toggle-row"><span><strong>Chế độ kiểm tra</strong><small>Không cung cấp đáp án trong mọi chế độ chat.</small></span><input type="checkbox" role="switch" name="ai_exam_mode" value="1" data-policy-exam @checked($examMode)><span class="tdash-switch-track" aria-hidden="true"></span></label>
            <p class="tdash-policy-hint" data-solution-note>Công tắc lời giải chỉ áp dụng cho chính sách “Giảng viên quyết định”.</p>
            <p class="tdash-policy-preview" data-policy-preview aria-live="polite">{{ $examMode ? 'Chế độ kiểm tra đang bật: không cung cấp đáp án.' : $policyOptions[$selectedPolicy] }}</p>
            <button type="submit" class="tdash-button tdash-policy-save">Lưu cấu hình</button>
            <a class="tdash-policy-fallback" href="{{ route('courses.lessons.ai-policy.edit', [$course->id, $policyLesson->id]) }}">Mở trang chính sách →</a>
            <p class="tdash-policy-hint">Chỉ áp dụng cho bài học đang chọn. Thay đổi có hiệu lực sau khi lưu.</p>
        </form>
    </details>
@else
    <p class="tdash-policy-hint">Thêm bài học vào khóa học để cấu hình Gia sư AI.</p>
@endif
