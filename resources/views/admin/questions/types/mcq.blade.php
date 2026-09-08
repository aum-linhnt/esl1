{{-- MCQ & Audio Listening Module --}}
<div x-show="singleQType === 'mcq' || singleQType === 'audio_listening'" class="space-y-3">
    <div>
        <label class="block text-xs font-bold text-gray-300 mb-1">
            Các lựa chọn trắc nghiệm (Mỗi dòng 1 lựa chọn): <span class="text-red-400">*</span>
        </label>
        <textarea name="options" rows="4" placeholder="A. Lựa chọn 1&#10;B. Lựa chọn 2&#10;C. Lựa chọn 3&#10;D. Lựa chọn 4" class="login-input text-xs font-mono"></textarea>
        <p class="text-[10px] text-gray-500 mt-1">Gõ mỗi phương án lựa chọn trên một dòng riêng biệt.</p>
    </div>
    <div>
        <label class="block text-xs font-bold text-emerald-400 mb-1">
            Đáp án đúng chính xác: <span class="text-red-400">*</span>
        </label>
        <input type="text" name="correct_answer" placeholder="A. Lựa chọn 1 (Khớp chính xác 1 dòng trên)" class="login-input text-xs font-mono text-emerald-300 font-bold">
    </div>
</div>
