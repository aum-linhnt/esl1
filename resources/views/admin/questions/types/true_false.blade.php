{{-- True / False / Not Given Question Module --}}
<div x-show="singleQType === 'true_false'" class="space-y-3" x-cloak>
    <div>
        <label class="block text-xs font-bold text-gray-300 mb-1">
            Đáp án đúng cho nhận định: <span class="text-red-400">*</span>
        </label>
        <div x-data="{ answer: 'True' }">
            <input type="hidden" name="true_false_answer" :value="answer">
            <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="answer = 'True'"
                        :class="answer === 'True' ? 'bg-emerald-600/30 border-emerald-500 text-emerald-300 font-bold shadow-lg shadow-emerald-900/20' : 'bg-slate-900/80 border-slate-700 text-gray-400 hover:border-slate-600'"
                        class="p-2.5 rounded-xl border text-xs font-mono transition-all flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="answer === 'True' ? 'bg-emerald-400' : 'bg-gray-600'"></span>
                    <span>True (Đúng)</span>
                </button>
                <button type="button" @click="answer = 'False'"
                        :class="answer === 'False' ? 'bg-rose-600/30 border-rose-500 text-rose-300 font-bold shadow-lg shadow-rose-900/20' : 'bg-slate-900/80 border-slate-700 text-gray-400 hover:border-slate-600'"
                        class="p-2.5 rounded-xl border text-xs font-mono transition-all flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="answer === 'False' ? 'bg-rose-400' : 'bg-gray-600'"></span>
                    <span>False (Sai)</span>
                </button>
                <button type="button" @click="answer = 'Not Given'"
                        :class="answer === 'Not Given' ? 'bg-amber-600/30 border-amber-500 text-amber-300 font-bold shadow-lg shadow-amber-900/20' : 'bg-slate-900/80 border-slate-700 text-gray-400 hover:border-slate-600'"
                        class="p-2.5 rounded-xl border text-xs font-mono transition-all flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="answer === 'Not Given' ? 'bg-amber-400' : 'bg-gray-600'"></span>
                    <span>Not Given</span>
                </button>
            </div>
        </div>
        <p class="text-[10px] text-gray-500 mt-1">Chuẩn đề thi học thuật IELTS / CEFR Reading & Listening.</p>
    </div>
</div>
