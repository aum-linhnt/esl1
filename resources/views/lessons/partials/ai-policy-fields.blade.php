@php
    $policyLesson = $policyLesson ?? null;
    $policyModel = $policyModel ?? null;
    $policies = ['no_answer' => 'Không hỗ trợ đáp án', 'hints_only' => 'Chỉ gợi ý (cấp 1–3)',
        'hints_first' => 'Gợi ý trước, lời giải ở cấp 4', 'full_solution' => 'Cho phép lời giải ngay',
        'teacher_controlled' => 'Giáo viên quyết định quyền xem lời giải'];
@endphp
<fieldset class="space-y-3 rounded-xl border border-slate-700 p-3">
    <legend class="px-1 text-sm font-semibold">Chính sách Gia sư AI</legend>
    <label class="block text-xs">Hỗ trợ đáp án
        <select name="ai_answer_policy" class="login-input mt-1 !py-2 text-xs"
            @if($policyModel) x-model="{{ $policyModel }}.ai_answer_policy" @endif>
            @foreach($policies as $value => $label)
                <option value="{{ $value }}" @selected(old('ai_answer_policy', $policyLesson?->ai_answer_policy ?? 'hints_only') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <input type="hidden" name="ai_teacher_solution_allowed" value="0">
    <label class="flex items-start gap-2 text-xs">
        <input type="checkbox" name="ai_teacher_solution_allowed" value="1"
            @if($policyModel) x-model="{{ $policyModel }}.ai_teacher_solution_allowed"
            @else @checked(old('ai_teacher_solution_allowed', $policyLesson?->ai_teacher_solution_allowed ?? false)) @endif>
        <span>Cho phép cấp 4 khi chọn “Giáo viên quyết định”. Nếu tắt, chỉ gợi ý cấp 1–3.</span>
    </label>
    <input type="hidden" name="ai_exam_mode" value="0">
    <label class="flex items-start gap-2 text-xs">
        <input type="checkbox" name="ai_exam_mode" value="1"
            @if($policyModel) x-model="{{ $policyModel }}.ai_exam_mode"
            @else @checked(old('ai_exam_mode', $policyLesson?->ai_exam_mode ?? false)) @endif>
        <span>Bài thi: tắt hỗ trợ đáp án trong mọi chế độ chat.</span>
    </label>
    <p class="text-xs text-gray-400">Cấp 1: định hướng · 2: nhắc kiến thức · 3: bước gần nhất · 4: lời giải khi được phép. Học viên xin từng cấp tiếp theo; mỗi phản hồi AI dùng credit theo cấu hình.</p>
</fieldset>
