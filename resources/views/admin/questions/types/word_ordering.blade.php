{{-- Word Ordering Question Module --}}
<div x-show="singleQType === 'word_ordering'" class="space-y-3" x-cloak>
    <div>
        <label class="block text-xs font-bold text-gray-300 mb-1">
            Câu hoàn chỉnh đúng: <span class="text-red-400">*</span>
        </label>
        <input type="text" name="correct_answer" placeholder="Ví dụ: Renewable energy is vital for the future" class="login-input text-xs font-mono text-emerald-300">
    </div>
    <div>
        <label class="block text-xs font-bold text-gray-400 mb-1">
            Các từ xáo trộn (Cách nhau dấu phẩy, bỏ trống để tự tách từ theo khoảng trắng):
        </label>
        <input type="text" name="word_tiles" placeholder="Renewable, energy, is, vital, for, the, future" class="login-input text-xs font-mono">
    </div>
</div>
