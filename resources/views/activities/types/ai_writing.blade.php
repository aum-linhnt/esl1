{{-- Activity Type: AI Writing Task --}}
<div class="space-y-4" x-data="inlineWritingApp()">
    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 space-y-3">
        <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">✍️ Đề bài tự luận</span>
        <p class="text-sm font-semibold text-white leading-relaxed">{{ $content['prompt'] ?? 'Write a short paragraph about your daily routine.' }}</p>
        <span class="text-[10px] text-gray-400">Yêu cầu tối thiểu: {{ $content['min_words'] ?? 50 }} từ</span>
    </div>

    <div>
        <textarea x-model="essay" rows="5" placeholder="Nhập bài viết của bạn tại đây..." class="login-input text-xs leading-relaxed"></textarea>
        <div class="flex items-center justify-between text-[10px] text-gray-400 mt-1">
            <span x-text="'Số từ: ' + wordCount"></span>
            <a href="{{ route('ai.writing.index') }}" target="_blank" class="text-indigo-400 hover:underline">Mở AI Writing Examiner đầy đủ</a>
        </div>
    </div>

    @include('activities._completion-button', ['label' => 'Nộp bài tự luận ✓'])
</div>

<script>
function inlineWritingApp() {
    return {
        essay: '',
        get wordCount() {
            return this.essay.trim() ? this.essay.trim().split(/\s+/).length : 0;
        }
    };
}
</script>
