@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="aiWritingApp()">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-gradient-to-r from-purple-500 to-indigo-500 text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full shadow-glow-purple">
                    AI Writing Assistant
                </span>
                <span class="text-xs text-gray-400">Chấm tự luận theo chuẩn CEFR / IELTS</span>
            </div>
            <h2 class="text-2xl font-bold text-white">Luyện viết & Chấm bài cùng AI</h2>
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-400 bg-fsel-navy border border-fsel-border px-3.5 py-2 rounded-xl">
            <span>Thưởng khi nộp bài:</span>
            <span class="text-teal-300 font-bold flex items-center gap-1 font-mono">
                ⚡ +15 XP
            </span>
        </div>
    </div>

    {{-- Main Workspace --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Left: Essay Input Editor (7 cols) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="card-dark p-6 space-y-4">
                {{-- Topic Selector --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-2">Chọn chủ đề gợi ý hoặc tự nhập:</label>
                    <div class="space-y-2 mb-3">
                        @foreach($sampleTopics as $idx => $t)
                            <button
                                @click="topic = '{{ addslashes($t['title']) }}'; level = '{{ $t['level'] }}'"
                                type="button"
                                class="w-full text-left p-3 rounded-lg border text-xs transition-all flex items-center justify-between"
                                :class="topic === '{{ addslashes($t['title']) }}' ? 'bg-fsel-teal/15 border-fsel-teal text-white ring-1 ring-fsel-teal/40' : 'bg-fsel-navy/40 border-fsel-border/30 text-gray-400 hover:text-white hover:border-fsel-accent/40'">
                                <span class="line-clamp-1 flex-1">{{ $t['title'] }}</span>
                                <span class="ml-2 font-mono font-bold text-[10px] px-2 py-0.5 rounded bg-fsel-dark text-fsel-teal">{{ $t['level'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <input type="text" x-model="topic" placeholder="Nhập chủ đề bài viết..." class="login-input !py-2 text-xs">
                        </div>
                        <div class="relative" x-data="{
                            open: false,
                            options: {
                                'A1': 'Level A1',
                                'A2': 'Level A2',
                                'B1': 'Level B1',
                                'B2': 'Level B2',
                                'C1': 'Level C1'
                            }
                        }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="login-input !py-2 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                                <span x-text="options[level] || ('Level ' + level)" class="text-white truncate"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="level = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="level === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="level === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Textarea --}}
                <div>
                    <div class="flex justify-between items-center mb-1.5 text-xs text-gray-400">
                        <label class="font-semibold text-gray-300">Nội dung bài viết (Tiếng Anh):</label>
                        <span x-text="wordCount + ' từ · ' + essay.length + ' ký tự'"></span>
                    </div>
                    <textarea
                        x-model="essay"
                        rows="10"
                        placeholder="Type or paste your English essay here..."
                        class="login-input font-sans leading-relaxed text-sm"></textarea>
                </div>

                {{-- Action Button --}}
                <div class="flex items-center justify-between pt-2 border-t border-fsel-border/20">
                    <button
                        @click="essay = ''"
                        type="button"
                        class="text-xs text-gray-500 hover:text-red-400 transition-colors">
                        Xóa nội dung
                    </button>

                    <button
                        @click="analyzeEssay()"
                        :disabled="analyzing || wordCount < 5"
                        type="button"
                        class="btn-primary !w-auto !py-2.5 px-8 text-sm flex items-center gap-2 shadow-glow-blue"
                        :class="(analyzing || wordCount < 5) && 'opacity-40 cursor-not-allowed'">
                        <span x-show="!analyzing">✨ Phân tích bài viết</span>
                        <span x-show="analyzing" class="flex items-center gap-2">
                            <span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            AI đang chấm bài...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Right: AI Evaluation Report (5 cols) --}}
        <div class="lg:col-span-5 space-y-4">
            
            {{-- Placeholder when not yet analyzed --}}
            <div x-show="!result && !analyzing" class="card-dark p-8 text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-full bg-fsel-navy flex items-center justify-center text-2xl">
                    📝
                </div>
                <h3 class="text-base font-bold text-white">Báo cáo đánh giá AI</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Nhập bài viết và bấm <strong>Phân tích bài viết</strong> để nhận điểm số CEFR, danh sách lỗi ngữ pháp và gợi ý từ vựng nâng cao.
                </p>
            </div>

            {{-- Analysis Loading Skeleton --}}
            <div x-show="analyzing" x-cloak style="display: none;" class="card-dark p-6 space-y-4 animate-pulse">
                <div class="h-16 bg-fsel-navy rounded-xl"></div>
                <div class="h-24 bg-fsel-navy rounded-xl"></div>
                <div class="h-32 bg-fsel-navy rounded-xl"></div>
            </div>

            {{-- Result Panel --}}
            <div x-show="result && !analyzing" x-cloak style="display: none;" class="space-y-4">
                
                {{-- Score Summary Card --}}
                <div class="card-dark p-5 bg-gradient-to-br from-fsel-navy to-fsel-card border-fsel-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400">Điểm tổng quan</span>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-black text-white" x-text="result?.overall_score"></span>
                                <span class="text-xs text-gray-400">/ 100</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xs text-gray-400">Trình độ ước tính</span>
                            <p class="text-2xl font-black text-fsel-teal font-mono mt-1" x-text="result?.cefr_level"></p>
                        </div>
                    </div>
                </div>

                {{-- Grammar Errors List --}}
                <div class="card-dark p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-red-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>❌ Lỗi ngữ pháp & Diễn đạt</span>
                            <span class="bg-red-500/20 text-red-300 px-2 py-0.5 rounded-full text-[10px]" x-text="result?.grammar_errors?.length || 0"></span>
                        </h4>
                    </div>

                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        <template x-for="(err, i) in result?.grammar_errors" :key="i">
                            <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-xs space-y-1">
                                <div class="flex items-center gap-1.5 text-red-300">
                                    <span class="line-through font-mono" x-text="err.original"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    <span class="font-bold text-green-400 font-mono" x-text="err.fix"></span>
                                </div>
                                <p class="text-gray-400 text-[11px]" x-text="err.reason"></p>
                            </div>
                        </template>

                        <div x-show="!result?.grammar_errors?.length" class="text-xs text-green-400 italic">
                            ✓ Không phát hiện lỗi ngữ pháp nghiêm trọng!
                        </div>
                    </div>
                </div>

                {{-- Vocabulary Improvements --}}
                <div class="card-dark p-5 space-y-3">
                    <h4 class="text-xs font-bold text-fsel-gold uppercase tracking-wider flex items-center gap-1.5">
                        <span>💡 Gợi ý nâng cấp từ vựng Band cao</span>
                    </h4>

                    <div class="space-y-2">
                        <template x-for="(vocab, i) in result?.vocabulary_improvements" :key="i">
                            <div class="p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-lg text-xs space-y-1">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-gray-400 line-through" x-text="vocab.original"></span>
                                    <span class="text-fsel-gold font-bold text-sm" x-text="vocab.suggestion"></span>
                                </div>
                                <p class="text-gray-400 text-[11px]" x-text="vocab.explanation"></p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Model Essay --}}
                <div class="card-dark p-5 space-y-2">
                    <h4 class="text-xs font-bold text-fsel-teal uppercase tracking-wider">
                        ✨ Phiên bản hoàn thiện gợi ý (Model Essay)
                    </h4>
                    <p class="text-xs text-gray-300 leading-relaxed italic bg-fsel-navy/50 p-3 rounded-lg border border-fsel-border/30"
                       x-text="result?.model_essay"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function aiWritingApp() {
    return {
        topic: 'Describe your favorite hobby and why you enjoy it.',
        level: 'B1',
        essay: 'I like play football very much. Yesterday I go to the stadium with my friends and we have a very good match.',
        analyzing: false,
        result: null,

        get wordCount() {
            return this.essay.trim() ? this.essay.trim().split(/\s+/).length : 0;
        },

        analyzeEssay() {
            if (this.wordCount < 5 || this.analyzing) return;
            this.analyzing = true;

            fetch('{{ route("api.ai.writing.analyze") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    essay: this.essay,
                    topic: this.topic,
                    level: this.level
                })
            })
            .then(r => r.json())
            .then(res => {
                this.analyzing = false;
                if (res.success) {
                    this.result = res.data;
                    // Update XP counter in header
                    const xpEl = document.getElementById('xp-count');
                    if (xpEl && res.data.total_xp) {
                        xpEl.textContent = new Intl.NumberFormat().format(res.data.total_xp) + ' XP';
                    }
                }
            })
            .catch(() => {
                this.analyzing = false;
            });
        }
    };
}
</script>
@endsection
