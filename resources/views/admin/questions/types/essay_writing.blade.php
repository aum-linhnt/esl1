{{-- Essay Writing Question Module --}}
<div x-show="singleQType === 'essay_writing'" class="space-y-3" x-cloak>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-cyan-300 mb-1">
                Số từ tối thiểu (Minimum Word Count):
            </label>
            <input type="number" name="min_words" value="50" min="20" max="1000" class="login-input text-xs font-mono">
        </div>
        <div>
            <label class="block text-xs font-bold text-cyan-300 mb-1">
                Dạng bài viết (Task Type):
            </label>
            <input type="text" name="task_type" placeholder="email, essay, summary, review" class="login-input text-xs font-mono">
        </div>
    </div>
    <p class="text-[10px] text-gray-500">Bài viết của học viên sẽ được AI Gemini chấm điểm theo thang rubric tiêu chí chấm thi.</p>
</div>
