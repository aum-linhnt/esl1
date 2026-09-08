{{-- Advanced Filter Toolbar --}}
<div class="card-dark p-4 border-slate-800 space-y-3.5 relative z-30">
    <form method="GET" action="{{ route('admin.questions.index') }}" class="space-y-3">
        <input type="hidden" name="tab" value="questions">
        
        {{-- Row 1: Search & Skill Pills --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            {{-- Search Input --}}
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    🔍
                </span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Tìm kiếm câu hỏi, đáp án, hoặc tiêu đề bài đọc..." 
                       class="login-input pl-9 text-xs">
            </div>

            {{-- Level, Type, Filter Button --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- CEFR Level --}}
                <div class="min-w-[150px]" :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selected: '{{ $difficulty }}',
                    options: {
                        'all': 'Tất cả Bậc (CEFR)',
                        'A1': 'A1 - Sơ cấp',
                        'A2': 'A2 - Tiền trung cấp',
                        'B1': 'B1 - Trung cấp',
                        'B2': 'B2 - Trung cao cấp',
                        'C1': 'C1 - Cao cấp',
                        'Mixed': 'Mixed'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="difficulty" :value="selected">
                    <button type="button" @click="open = !open" class="login-input text-xs font-mono flex items-center justify-between cursor-pointer text-left w-full !py-2">
                        <span x-text="options[selected] || selected" class="text-white truncate"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Question Type --}}
                <div class="min-w-[170px]" :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selected: '{{ $questionType }}',
                    options: {
                        'all': 'Tất cả Dạng câu hỏi',
                        'mcq': 'Trắc nghiệm đơn (MCQ)',
                        'audio_listening': 'Nghe Audio',
                        'multiple_select': 'Chọn nhiều',
                        'fill_blank': 'Điền từ',
                        'matching': 'Nối cặp',
                        'true_false': 'True/False',
                        'essay_writing': 'Viết luận',
                        'audio_recording': 'Ghi âm nói'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="question_type" :value="selected">
                    <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2">
                        <span x-text="options[selected] || selected" class="text-white truncate"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>

                <button type="submit" class="btn-primary !w-auto !py-2 px-4 text-xs font-bold">
                    Lọc
                </button>

                @if(!empty($search) || $skill !== 'all' || $difficulty !== 'all' || $questionType !== 'all')
                    <a href="{{ route('admin.questions.index', ['tab' => 'questions']) }}" class="text-xs text-gray-400 hover:text-white px-2 py-1">
                        Xóa bộ lọc
                    </a>
                @endif
            </div>
        </div>

        {{-- Row 2: Skill Filter Quick Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 pt-1 border-t border-slate-800/60">
            <span class="text-[11px] text-gray-500 font-bold mr-1">Kỹ năng:</span>
            @php
                $skillPills = [
                    'all' => 'Tất cả',
                    'listening' => '🎧 Nghe (Listening)',
                    'reading' => '📖 Đọc (Reading)',
                    'writing' => '✍️ Viết (Writing)',
                    'speaking' => '🎙️ Nói (Speaking)',
                ];
            @endphp
            @foreach($skillPills as $skKey => $skLabel)
                <a href="{{ route('admin.questions.index', array_merge(request()->query(), ['skill' => $skKey])) }}"
                   class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all {{ $skill === $skKey ? 'bg-blue-600 text-white' : 'bg-slate-900 text-gray-400 hover:text-white border border-slate-800' }}">
                    {{ $skLabel }}
                </a>
            @endforeach
        </div>
    </form>
</div>
