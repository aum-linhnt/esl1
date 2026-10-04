@if(($avatarRole ?? 'learner') === 'tutor')
    <span class="lesson-avatar lesson-avatar--tutor" role="img" aria-label="Gia sư AI">
        @include('ai-tutor::partials.icon', ['name' => 'robot', 'size' => 24])
    </span>
@else
    @php
        $avatarUser = auth()->user();
        $avatarName = trim((string) ($avatarUser?->name ?? 'Học viên'));
        $avatarInitial = mb_strtoupper(mb_substr($avatarName ?: 'Học viên', 0, 1));
        $avatarUrl = !empty($avatarUser?->avatar) ? $avatarUser->avatar_url : null;
        if ($avatarUrl === asset('images/default-avatar.svg')) $avatarUrl = null;
    @endphp
    <span class="lesson-avatar lesson-avatar--learner" role="img" aria-label="{{ $avatarName ?: 'Học viên' }}">
        <span aria-hidden="true">{{ $avatarInitial }}</span>
        @if($avatarUrl)<img src="{{ $avatarUrl }}" alt="" data-learner-avatar referrerpolicy="no-referrer">@endif
    </span>
@endif
