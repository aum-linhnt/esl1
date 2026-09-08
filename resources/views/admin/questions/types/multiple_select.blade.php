{{-- Multiple Select Question Module --}}
<div x-show="singleQType === 'multiple_select'" class="space-y-3" x-cloak>
    <div>
        <label class="block text-xs font-bold text-gray-300 mb-1">
            Tất cả các lựa chọn (Mỗi dòng 1 lựa chọn): <span class="text-red-400">*</span>
        </label>
        <textarea name="options" rows="4" placeholder="Lựa chọn 1&#10;Lựa chọn 2&#10;Lựa chọn 3&#10;Lựa chọn 4" class="login-input text-xs font-mono"></textarea>
    </div>
    <div>
        <label class="block text-xs font-bold text-emerald-400 mb-1">
            Các đáp án đúng (Mỗi dòng 1 đáp án): <span class="text-red-400">*</span>
        </label>
        <textarea name="correct_answers_multi" rows="2" placeholder="Lựa chọn 1&#10;Lựa chọn 3" class="login-input text-xs font-mono text-emerald-300"></textarea>
        <p class="text-[10px] text-gray-500 mt-1">Hệ thống sẽ lưu mảng JSON các đáp án hợp lệ.</p>
    </div>
</div>
