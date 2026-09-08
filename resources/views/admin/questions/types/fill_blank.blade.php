{{-- Fill in the Blank Question Module --}}
<div x-show="singleQType === 'fill_blank'" class="space-y-3" x-cloak>
    <div>
        <label class="block text-xs font-bold text-emerald-400 mb-1">
            Từ / Cụm từ cần điền đúng: <span class="text-red-400">*</span>
        </label>
        <input type="text" name="correct_answer" placeholder="Ví dụ: sustainable" class="login-input text-xs font-mono text-emerald-300 font-bold">
        <p class="text-[10px] text-gray-500 mt-1">Học viên cần gõ chính xác từ hoặc cụm từ này để được tính điểm.</p>
    </div>
</div>
