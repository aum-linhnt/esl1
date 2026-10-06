@php
    $goalTargets = \App\Models\LearningGoal::targets();
    $goalFramework = old('framework', $initialFramework ?? $goal?->framework ?? 'cefr');
    if (!is_string($goalFramework) || !isset($goalTargets[$goalFramework])) $goalFramework = 'cefr';
    $goalTarget = old('target', $goal?->target ?? $user->target_level ?? 'B1');
    if (!is_string($goalTarget) || !isset($goalTargets[$goalFramework][$goalTarget])) $goalTarget = $goalFramework === 'toeic' ? '750' : ($goalFramework === 'ielts' ? '6.5' : 'B1');
    $goalDate = old('target_date', $goal?->target_date?->format('Y-m-d'));
    if (!is_string($goalDate)) $goalDate = '';
@endphp
<form class="dv2-goal-form" action="{{ route('dashboard.v2.goals.update') }}" method="post" data-learning-goal-form data-targets="{{ json_encode($goalTargets) }}">
    @csrf
    @method('PUT')
    <p class="dv2-form-description">Chọn đích đến và thời hạn để nhận bài luyện phù hợp mỗi ngày.</p>
    @if($errors->learningGoal->any())<div class="dv2-form-error" role="alert">@foreach($errors->learningGoal->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(!$goalReady)<div class="dv2-form-error" role="alert">Mục tiêu học tập chưa sẵn sàng. Vui lòng thử lại sau.</div>@endif
    <label>Hệ mục tiêu<select name="framework" required data-goal-framework>
        @foreach(['cefr' => 'CEFR — Trình độ tiếng Anh', 'toeic' => 'TOEIC — Tiếng Anh công việc', 'ielts' => 'IELTS — Tiếng Anh học thuật'] as $key => $text)<option value="{{ $key }}" @selected($goalFramework === $key)>{{ $text }}</option>@endforeach
    </select></label>
    <label>Trình độ / điểm mục tiêu<select name="target" required data-goal-target>
        @foreach($goalTargets[$goalFramework] as $value => $text)<option value="{{ $value }}" @selected((string)$goalTarget === (string)$value)>{{ $text }}</option>@endforeach
    </select></label>
    <label>Thời hạn dự kiến <span>(không bắt buộc)</span><input type="date" name="target_date" min="{{ today()->format('Y-m-d') }}" value="{{ $goalDate }}"></label>
    <p class="dv2-form-description">Bạn có thể thay đổi mục tiêu bất cứ lúc nào.</p>
    <div class="dv2-form-actions"><button type="submit" class="dv2-primary" @disabled(!$goalReady)>Lưu mục tiêu</button><a href="{{ route('dashboard.v2') }}" data-goal-close>Hủy</a></div>
</form>
