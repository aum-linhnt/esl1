{{-- Activity Type: H5P Interactive Content (Native Content Authoring, Standalone Package & URL Embed) --}}
@php
    $source = $content['source'] ?? ($activity->file_id ? 'upload' : (!empty($content['data']) ? 'editor' : 'url'));
    $hasEditorData = !empty($content['data']) || !empty($content['h5p_type']);
    $hasFile = !empty($activity->file_id) && !empty($activity->file);
    $extractedUrl = null;
    if ($hasFile) {
        $extractedUrl = $activity->getH5pExtractedUrl();
    }
    $hasEmbedUrl = !empty($content['embed_url']);
@endphp

<div class="space-y-4">
    {{-- Instructions --}}
    @if(!empty($content['instructions']))
        <div class="p-4 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 text-sm text-slate-700 dark:text-gray-200 leading-relaxed shadow-xs">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-base">💡</span>
                <span class="text-xs font-bold text-sky-700 dark:text-sky-300 uppercase tracking-wider">Hướng dẫn</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-gray-300">{{ $content['instructions'] }}</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CASE 1: AUTHORED H5P INTERACTIVE CONTENT (NATIVE PLAYER)           --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($source === 'editor' || ($hasEditorData && $source !== 'upload' && $source !== 'url'))
        @php
            $h5pType = $content['h5p_type'] ?? 'flashcards';
            $h5pData = $content['data'] ?? [];
        @endphp

        <script>
        window.__h5pConfig_{{ $activity->id }} = {
            type: {!! json_encode($h5pType) !!},
            data: {!! json_encode($h5pData) !!}
        };

        if (typeof window.h5pNativePlayer !== 'function') {
            window.h5pNativePlayer = function(type, rawData) {
                const data = rawData || {};
                return {
                    h5pType: type || 'flashcards',
                    h5pData: {
                        cards: [],
                        options: [],
                        questions: [],
                        ...data
                    },
                    score: 0,
                    maxScore: 100,

                    // Flashcard state
                    currentCardIndex: 0,
                    cardFlipped: false,
                    userAnswer: '',
                    cardChecked: false,
                    cardCorrect: false,

                    // MCQ state
                    mcqSelected: null,
                    mcqChecked: false,
                    mcqFeedback: '',

                    // Drag the Words state
                    dragSegments: [],
                    availableChips: [],
                    placedChips: {},
                    dragChecked: false,

                    // Blanks state
                    blanksSegments: [],
                    userBlanks: {},
                    blanksChecked: false,

                    // True / False state
                    tfSelected: null,
                    tfChecked: false,
                    tfFeedback: '',

                    get currentCard() {
                        const cards = this.h5pData.cards || [];
                        return cards[this.currentCardIndex] || {};
                    },

                    getTypeBadge() {
                        const map = {
                            flashcards: 'Flashcards',
                            multichoice: 'Multiple Choice',
                            drag_words: 'Drag the Words',
                            blanks: 'Fill in the Blanks',
                            true_false: 'True / False',
                            question_set: 'Question Set'
                        };
                        return map[this.h5pType] || this.h5pType;
                    },

                    initPlayer() {
                        if (this.h5pType === 'flashcards') {
                            if (!this.h5pData.cards) this.h5pData.cards = [];
                            this.maxScore = this.h5pData.cards.length * 10 || 10;
                        } else if (this.h5pType === 'multichoice') {
                            this.maxScore = 100;
                        } else if (this.h5pType === 'drag_words') {
                            this.parseDragSegments();
                        } else if (this.h5pType === 'blanks') {
                            this.parseBlanksSegments();
                        } else if (this.h5pType === 'true_false') {
                            this.maxScore = 100;
                        } else if (this.h5pType === 'question_set') {
                            this.maxScore = ((this.h5pData.questions || []).length || 1) * 10;
                        }
                    },

                    resetPlayer() {
                        this.score = 0;
                        this.currentCardIndex = 0;
                        this.cardFlipped = false;
                        this.userAnswer = '';
                        this.cardChecked = false;
                        this.resetMcq();
                        this.resetDragWords();
                        this.resetBlanks();
                        this.resetTf();
                    },

                    // ── Flashcard Methods ──
                    checkFlashcard() {
                        if (!this.userAnswer.trim()) return;
                        this.cardChecked = true;
                        const correctAns = (this.currentCard.answer || '').trim();
                        const isCaseSensitive = !!this.h5pData.case_sensitive;
                        if (isCaseSensitive) {
                            this.cardCorrect = this.userAnswer.trim() === correctAns;
                        } else {
                            this.cardCorrect = this.userAnswer.trim().toLowerCase() === correctAns.toLowerCase();
                        }
                        if (this.cardCorrect) {
                            this.score += 10;
                        }
                    },
                    nextCard() {
                        if (this.currentCardIndex < (this.h5pData.cards || []).length - 1) {
                            this.currentCardIndex++;
                            this.cardFlipped = false;
                            this.userAnswer = '';
                            this.cardChecked = false;
                        } else {
                            this.finishActivity();
                        }
                    },
                    prevCard() {
                        if (this.currentCardIndex > 0) {
                            this.currentCardIndex--;
                            this.cardFlipped = false;
                            this.userAnswer = '';
                            this.cardChecked = false;
                        }
                    },

                    // ── MCQ Methods ──
                    selectMcqOption(idx) {
                        this.mcqSelected = idx;
                    },
                    checkMcq() {
                        if (this.mcqSelected === null) return;
                        this.mcqChecked = true;
                        const selectedOpt = (this.h5pData.options || [])[this.mcqSelected];
                        if (selectedOpt && selectedOpt.is_correct) {
                            this.score = 100;
                            this.mcqFeedback = selectedOpt.feedback || 'Chúc mừng! Bạn đã chọn đúng.';
                            this.finishActivity();
                        } else {
                            this.score = 0;
                            this.mcqFeedback = selectedOpt?.feedback || 'Phương án chưa chính xác. Hãy thử lại!';
                        }
                    },
                    resetMcq() {
                        this.mcqSelected = null;
                        this.mcqChecked = false;
                        this.mcqFeedback = '';
                    },
                    getOptionClass(idx) {
                        if (!this.mcqChecked) {
                            return this.mcqSelected === idx
                                ? 'bg-sky-50 dark:bg-sky-950/30 border-sky-400 dark:border-sky-500 shadow-xs'
                                : 'bg-white dark:bg-slate-800/80 border-slate-200 dark:border-slate-700/80 hover:border-slate-300';
                        }
                        const opt = (this.h5pData.options || [])[idx] || {};
                        if (opt.is_correct) {
                            return 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-400 dark:border-emerald-500 text-emerald-800 dark:text-emerald-300 font-bold';
                        }
                        if (this.mcqSelected === idx && !opt.is_correct) {
                            return 'bg-rose-50 dark:bg-rose-950/30 border-rose-400 dark:border-rose-500 text-rose-800 dark:text-rose-300';
                        }
                        return 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 opacity-60';
                    },

                    // ── Drag the Words Methods ──
                    parseDragSegments() {
                        const text = this.h5pData.text || '';
                        const parts = text.split(/\*([^*]+)\*/g);
                        this.dragSegments = [];
                        this.availableChips = [];
                        this.placedChips = {};
                        let targetIdx = 0;
                        for (let i = 0; i < parts.length; i++) {
                            if (i % 2 === 0) {
                                this.dragSegments.push({ text: parts[i], isTarget: false });
                            } else {
                                this.dragSegments.push({ text: '', isTarget: true, targetIndex: targetIdx, answer: parts[i] });
                                this.availableChips.push(parts[i]);
                                targetIdx++;
                            }
                        }
                        this.availableChips.sort(() => Math.random() - 0.5);
                        this.maxScore = targetIdx * 10 || 10;
                    },
                    placeNextChip(chip, chipIdx) {
                        const targets = this.dragSegments.filter(s => s.isTarget);
                        for (const t of targets) {
                            if (!this.placedChips[t.targetIndex]) {
                                this.placedChips[t.targetIndex] = chip;
                                this.availableChips.splice(chipIdx, 1);
                                break;
                            }
                        }
                    },
                    removePlacedChip(targetIndex) {
                        if (this.dragChecked) return;
                        const chip = this.placedChips[targetIndex];
                        if (chip) {
                            delete this.placedChips[targetIndex];
                            this.availableChips.push(chip);
                        }
                    },
                    checkDragWords() {
                        this.dragChecked = true;
                        let correctCount = 0;
                        const targets = this.dragSegments.filter(s => s.isTarget);
                        targets.forEach(t => {
                            if (this.placedChips[t.targetIndex] && this.placedChips[t.targetIndex].trim().toLowerCase() === t.answer.trim().toLowerCase()) {
                                correctCount++;
                            }
                        });
                        this.score = correctCount * 10;
                        this.finishActivity();
                    },
                    resetDragWords() {
                        this.dragChecked = false;
                        this.parseDragSegments();
                    },
                    getDropTargetClass(targetIndex) {
                        if (!this.dragChecked) {
                            return this.placedChips[targetIndex]
                                ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400 text-sky-800 dark:text-sky-300'
                                : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-400';
                        }
                        const target = this.dragSegments.find(s => s.isTarget && s.targetIndex === targetIndex);
                        const isRight = target && this.placedChips[targetIndex] && this.placedChips[targetIndex].trim().toLowerCase() === target.answer.trim().toLowerCase();
                        return isRight
                            ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 text-emerald-800 dark:text-emerald-300'
                            : 'bg-rose-50 dark:bg-rose-950/40 border-rose-500 text-rose-800 dark:text-rose-300';
                    },

                    // ── Fill in the Blanks Methods ──
                    parseBlanksSegments() {
                        const text = this.h5pData.text || '';
                        const parts = text.split(/\*([^*]+)\*/g);
                        this.blanksSegments = [];
                        this.userBlanks = {};
                        let targetIdx = 0;
                        for (let i = 0; i < parts.length; i++) {
                            if (i % 2 === 0) {
                                this.blanksSegments.push({ text: parts[i], isTarget: false });
                            } else {
                                this.blanksSegments.push({ text: '', isTarget: true, targetIndex: targetIdx, answer: parts[i] });
                                targetIdx++;
                            }
                        }
                        this.maxScore = targetIdx * 10 || 10;
                    },
                    checkBlanks() {
                        this.blanksChecked = true;
                        let correctCount = 0;
                        const targets = this.blanksSegments.filter(s => s.isTarget);
                        targets.forEach(t => {
                            const userVal = (this.userBlanks[t.targetIndex] || '').trim();
                            const allowed = t.answer.split('/').map(a => a.trim());
                            const match = allowed.some(a => this.h5pData.case_sensitive ? a === userVal : a.toLowerCase() === userVal.toLowerCase());
                            if (match) correctCount++;
                        });
                        this.score = correctCount * 10;
                        this.finishActivity();
                    },
                    resetBlanks() {
                        this.blanksChecked = false;
                        this.parseBlanksSegments();
                    },
                    getBlankInputClass(targetIndex) {
                        if (!this.blanksChecked) {
                            return 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white';
                        }
                        const target = this.blanksSegments.find(s => s.isTarget && s.targetIndex === targetIndex);
                        const userVal = (this.userBlanks[targetIndex] || '').trim();
                        const allowed = target ? target.answer.split('/').map(a => a.trim()) : [];
                        const match = allowed.some(a => this.h5pData.case_sensitive ? a === userVal : a.toLowerCase() === userVal.toLowerCase());
                        return match
                            ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-400 text-emerald-800 dark:text-emerald-300 font-bold'
                            : 'bg-rose-50 dark:bg-rose-950/30 border-rose-400 text-rose-800 dark:text-rose-300';
                    },

                    // ── True / False Methods ──
                    selectTf(val) {
                        this.tfSelected = val;
                    },
                    checkTf() {
                        if (this.tfSelected === null) return;
                        this.tfChecked = true;
                        const isCorrect = this.tfSelected === !!this.h5pData.correct_answer;
                        if (isCorrect) {
                            this.score = 100;
                            this.tfFeedback = this.h5pData.feedback_true || 'Chính xác! Câu trả lời hoàn toàn đúng.';
                        } else {
                            this.score = 0;
                            this.tfFeedback = this.h5pData.feedback_false || 'Chưa chính xác. Hãy xem lại kiến thức!';
                        }
                        this.finishActivity();
                    },
                    resetTf() {
                        this.tfSelected = null;
                        this.tfChecked = false;
                        this.tfFeedback = '';
                    },
                    getTfClass(val) {
                        if (!this.tfChecked) {
                            return this.tfSelected === val
                                ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-500 text-sky-800 dark:text-sky-300 scale-102 font-bold'
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-gray-300 hover:border-slate-300';
                        }
                        const correctVal = !!this.h5pData.correct_answer;
                        if (val === correctVal) {
                            return 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-500 text-emerald-700 dark:text-emerald-300 font-bold';
                        }
                        if (this.tfSelected === val && val !== correctVal) {
                            return 'bg-rose-50 dark:bg-rose-950/30 border-rose-500 text-rose-700 dark:text-rose-300';
                        }
                        return 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 opacity-60';
                    },

                    // ── Question Set Methods ──
                    finishQuestionSet(answers) {
                        let correctCount = 0;
                        (this.h5pData.questions || []).forEach((q, idx) => {
                            if (answers[idx] === q.answer) correctCount++;
                        });
                        this.score = correctCount * 10;
                        this.finishActivity();
                    },

                    finishActivity() {
                        const finalPercentage = Math.round((this.score / Math.max(this.maxScore, 1)) * 100);
                        const alpineEl = document.querySelector('[x-data*="activityTelemetry"]');
                        if (alpineEl && window.Alpine) {
                            try {
                                const data = Alpine.$data(alpineEl);
                                if (data && typeof data.markCompleted === 'function') {
                                    data.markCompleted(finalPercentage);
                                }
                            } catch (e) {
                                console.log('Alpine auto complete:', e);
                            }
                        }
                    }
                };
            };
        }
        </script>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/90 shadow-lg overflow-hidden p-4 sm:p-6"
             x-data="window.h5pNativePlayer(window.__h5pConfig_{{ $activity->id }}.type, window.__h5pConfig_{{ $activity->id }}.data)"
             x-init="initPlayer()">
            
            {{-- Top Info Bar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-slate-800/80">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-sky-500/15 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold text-sm border border-sky-500/30">
                        🧩
                    </span>
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>H5P Tương tác</span>
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-sky-100 dark:bg-sky-500/20 text-sky-700 dark:text-sky-300" x-text="getTypeBadge()"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 dark:text-gray-400" x-text="h5pData.description || 'Hoàn thành bài tập tương tác bên dưới'"></p>
                    </div>
                </div>

                {{-- Score Badge & Reset --}}
                <div class="flex items-center gap-2">
                    <div class="px-3 py-1 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-mono font-bold flex items-center gap-1.5 text-slate-700 dark:text-gray-300">
                        <span>🎯 Điểm:</span>
                        <span class="text-emerald-600 dark:text-emerald-400" x-text="score + ' / ' + maxScore"></span>
                    </div>
                    <button type="button" @click="resetPlayer()" title="Làm lại từ đầu" class="p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white transition-colors text-xs">
                        🔄
                    </button>
                </div>
            </div>

            {{-- 1. FLASHCARDS PLAYER --}}
            <template x-if="h5pType === 'flashcards'">
                <div class="max-w-xl mx-auto space-y-5">
                    {{-- Progress Bar --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-mono text-slate-500 dark:text-gray-400 font-bold">
                            <span>Thẻ <strong class="text-sky-600 dark:text-sky-400" x-text="currentCardIndex + 1"></strong> / <span x-text="(h5pData.cards || []).length"></span></span>
                            <span>Đúng: <strong class="text-emerald-600 dark:text-emerald-400" x-text="score"></strong></span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-500 to-indigo-600 transition-all duration-300"
                                 :style="'width: ' + (((currentCardIndex + 1) / Math.max((h5pData.cards || []).length, 1)) * 100) + '%'"></div>
                        </div>
                    </div>

                    {{-- 3D Flip Card Container --}}
                    <div class="relative min-h-[260px] rounded-2xl border-2 transition-all duration-300 p-6 flex flex-col items-center justify-center text-center cursor-pointer select-none shadow-md"
                         :class="cardFlipped ? 'bg-indigo-50/50 dark:bg-indigo-950/30 border-indigo-300 dark:border-indigo-500/50' : 'bg-slate-50/70 dark:bg-slate-950/60 border-slate-200 dark:border-slate-800 hover:border-sky-300 dark:hover:border-sky-500/40'"
                         @click="cardFlipped = !cardFlipped">
                        
                        {{-- Front Side --}}
                        <div x-show="!cardFlipped" class="space-y-3 w-full">
                            <template x-if="currentCard.image_url">
                                <img :src="currentCard.image_url" alt="Flashcard image" class="max-h-36 mx-auto rounded-xl object-contain shadow-xs">
                            </template>
                            <p class="text-base sm:text-lg font-bold text-slate-900 dark:text-white" x-text="currentCard.question || 'Không có nội dung câu hỏi'"></p>
                            <p class="text-[11px] text-slate-400 dark:text-gray-500 font-mono">Bấm vào thẻ để lật xem gợi ý / đáp án ↻</p>
                        </div>

                        {{-- Back Side (Flipped) --}}
                        <div x-show="cardFlipped" x-cloak class="space-y-2 w-full">
                            <span class="text-[10px] font-mono uppercase font-bold text-indigo-600 dark:text-indigo-400">Đáp án chuẩn:</span>
                            <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-300 flex items-center justify-center gap-2">
                                <span x-text="currentCard.answer"></span>
                                <button type="button" @click.stop="speakWord(currentCard.answer)" title="Nghe phát âm" class="p-1 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-500/20 text-base">
                                    🔊
                                </button>
                            </div>
                            <template x-if="currentCard.tip">
                                <p class="text-xs text-slate-500 dark:text-gray-400 italic">💡 Gợi ý: <span x-text="currentCard.tip"></span></p>
                            </template>
                        </div>
                    </div>

                    {{-- Answer Input & Verification --}}
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" x-model="userAnswer" @keyup.enter="checkFlashcard()"
                                   :disabled="cardChecked"
                                   placeholder="Nhập câu trả lời bằng tiếng Anh..."
                                   class="login-input text-sm flex-1 font-semibold">
                            <button type="button" @click="checkFlashcard()" :disabled="cardChecked || !userAnswer.trim()"
                                    class="btn-primary !w-auto !py-2 px-5 text-xs font-bold shadow-glow-blue disabled:opacity-50">
                                Kiểm tra
                            </button>
                        </div>

                        {{-- Verification Result Feedback --}}
                        <template x-if="cardChecked">
                            <div class="p-3 rounded-xl border text-xs font-semibold flex items-center justify-between transition-all"
                                 :class="cardCorrect ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-500/30'">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg" x-text="cardCorrect ? '🎉' : '❌'"></span>
                                    <div>
                                        <span x-text="cardCorrect ? 'Chính xác! Xuất sắc.' : 'Chưa đúng rồi!'"></span>
                                        <template x-if="!cardCorrect">
                                            <span class="block text-[11px] font-mono mt-0.5">Đáp án đúng là: <strong class="text-indigo-600 dark:text-indigo-300" x-text="currentCard.answer"></strong></span>
                                        </template>
                                    </div>
                                </div>
                                <button type="button" @click="speakWord(currentCard.answer)" class="text-base p-1 hover:opacity-80">🔊</button>
                            </div>
                        </template>

                        {{-- Card Navigation Controls --}}
                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="prevCard()" :disabled="currentCardIndex === 0"
                                    class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 transition-colors">
                                ← Thẻ trước
                            </button>
                            <button type="button" @click="nextCard()"
                                    class="px-4 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm transition-all">
                                <span x-text="currentCardIndex >= (h5pData.cards || []).length - 1 ? 'Xem kết quả ★' : 'Thẻ tiếp theo →'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- 2. MULTIPLE CHOICE QUESTION PLAYER --}}
            <template x-if="h5pType === 'multichoice'">
                <div class="max-w-xl mx-auto space-y-5">
                    <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-relaxed" x-text="h5pData.question"></h3>
                    </div>

                    {{-- Options List --}}
                    <div class="space-y-2.5">
                        <template x-for="(opt, oIdx) in h5pData.options" :key="oIdx">
                            <div class="p-3.5 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-3"
                                 :class="getOptionClass(oIdx)"
                                 @click="!mcqChecked && selectMcqOption(oIdx)">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-lg border flex items-center justify-center font-mono font-bold text-xs"
                                          :class="mcqSelected === oIdx ? 'bg-sky-500 text-white border-sky-500' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-gray-300'"
                                          x-text="String.fromCharCode(65 + oIdx)"></span>
                                    <span class="text-xs font-medium text-slate-800 dark:text-gray-200" x-text="opt.text"></span>
                                </div>
                                <template x-if="mcqChecked">
                                    <span class="text-sm" x-text="opt.is_correct ? '✓' : (mcqSelected === oIdx ? '✗' : '')"></span>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Check & Feedback --}}
                    <div class="pt-2 flex items-center justify-between">
                        <button type="button" @click="checkMcq()" :disabled="mcqChecked || mcqSelected === null"
                                class="btn-primary !w-auto !py-2 px-6 text-xs font-bold shadow-glow-blue disabled:opacity-50">
                            Kiểm tra đáp án
                        </button>
                        <template x-if="mcqChecked">
                            <button type="button" @click="resetMcq()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 transition-colors">
                                Thử lại 🔄
                            </button>
                        </template>
                    </div>

                    {{-- Explanation feedback --}}
                    <template x-if="mcqChecked && mcqFeedback">
                        <div class="p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-500/30 text-xs text-indigo-900 dark:text-indigo-200">
                            <span class="font-bold">💡 Giải thích:</span> <span x-text="mcqFeedback"></span>
                        </div>
                    </template>
                </div>
            </template>

            {{-- 3. DRAG THE WORDS PLAYER --}}
            <template x-if="h5pType === 'drag_words'">
                <div class="max-w-2xl mx-auto space-y-5">
                    <div class="p-4 rounded-2xl bg-sky-50/50 dark:bg-sky-950/20 border border-sky-200/60 dark:border-sky-500/20 text-xs text-slate-600 dark:text-gray-300">
                        <span>💡 <strong>Hướng dẫn:</strong> Bấm vào từ bên dưới để đặt vào ô trống tiếp theo, hoặc bấm vào ô đã điền để lấy lại từ.</span>
                    </div>

                    {{-- Text Passage with Drop Targets --}}
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-sm leading-loose text-slate-800 dark:text-gray-200 font-medium">
                        <template x-for="(seg, sIdx) in dragSegments" :key="sIdx">
                            <span class="inline">
                                <span x-text="seg.text"></span>
                                <template x-if="seg.isTarget">
                                    <span class="inline-flex items-center justify-center min-w-[90px] h-7 px-2.5 mx-1 my-0.5 rounded-lg border-2 border-dashed transition-all cursor-pointer text-xs font-bold font-mono align-middle"
                                          :class="getDropTargetClass(seg.targetIndex)"
                                          @click="removePlacedChip(seg.targetIndex)"
                                          x-text="placedChips[seg.targetIndex] ? placedChips[seg.targetIndex] : '____'">
                                    </span>
                                </template>
                            </span>
                        </template>
                    </div>

                    {{-- Chips Dock --}}
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-2">
                        <div class="text-[11px] uppercase font-bold text-slate-400 font-mono">Các từ cần kéo thả:</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(chip, cIdx) in availableChips" :key="cIdx">
                                <button type="button" @click="placeNextChip(chip, cIdx)"
                                        :disabled="dragChecked"
                                        class="px-3 py-1.5 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/30 text-sky-700 dark:text-sky-300 font-mono font-bold text-xs shadow-xs transition-all hover:scale-105 active:scale-95 cursor-pointer">
                                    <span x-text="chip"></span>
                                </button>
                            </template>
                            <template x-if="availableChips.length === 0">
                                <span class="text-xs text-slate-400 italic">Tất cả các từ đã được đặt vào chỗ trống.</span>
                            </template>
                        </div>
                    </div>

                    {{-- Controls --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="checkDragWords()" :disabled="dragChecked"
                                class="btn-primary !w-auto !py-2 px-6 text-xs font-bold shadow-glow-blue disabled:opacity-50">
                            Kiểm tra kết quả
                        </button>
                        <template x-if="dragChecked">
                            <button type="button" @click="resetDragWords()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 transition-colors">
                                Thử lại 🔄
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- 4. FILL IN THE BLANKS PLAYER --}}
            <template x-if="h5pType === 'blanks'">
                <div class="max-w-2xl mx-auto space-y-5">
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-sm leading-loose text-slate-800 dark:text-gray-200 font-medium">
                        <template x-for="(seg, sIdx) in blanksSegments" :key="sIdx">
                            <span class="inline">
                                <span x-text="seg.text"></span>
                                <template x-if="seg.isTarget">
                                    <input type="text" x-model="userBlanks[seg.targetIndex]"
                                           :disabled="blanksChecked"
                                           class="inline-block w-32 py-1 px-2.5 mx-1 my-0.5 rounded-lg border text-xs font-mono font-bold transition-all"
                                           :class="getBlankInputClass(seg.targetIndex)"
                                           placeholder="...">
                                </template>
                            </span>
                        </template>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="checkBlanks()" :disabled="blanksChecked"
                                class="btn-primary !w-auto !py-2 px-6 text-xs font-bold shadow-glow-blue disabled:opacity-50">
                            Kiểm tra đáp án
                        </button>
                        <template x-if="blanksChecked">
                            <button type="button" @click="resetBlanks()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 transition-colors">
                                Thử lại 🔄
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- 5. TRUE / FALSE QUESTION PLAYER --}}
            <template x-if="h5pType === 'true_false'">
                <div class="max-w-xl mx-auto space-y-5">
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-relaxed" x-text="h5pData.question"></h3>
                    </div>

                    {{-- True / False Interactive Cards --}}
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" @click="!tfChecked && selectTf(true)"
                                class="p-5 rounded-2xl border-2 transition-all flex flex-col items-center justify-center gap-2 cursor-pointer shadow-xs"
                                :class="getTfClass(true)">
                            <span class="text-3xl">✓</span>
                            <span class="font-bold text-sm">ĐÚNG (TRUE)</span>
                        </button>
                        <button type="button" @click="!tfChecked && selectTf(false)"
                                class="p-5 rounded-2xl border-2 transition-all flex flex-col items-center justify-center gap-2 cursor-pointer shadow-xs"
                                :class="getTfClass(false)">
                            <span class="text-3xl">✗</span>
                            <span class="font-bold text-sm">SAI (FALSE)</span>
                        </button>
                    </div>

                    {{-- Check & Feedback --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="checkTf()" :disabled="tfChecked || tfSelected === null"
                                class="btn-primary !w-auto !py-2 px-6 text-xs font-bold shadow-glow-blue disabled:opacity-50">
                            Kiểm tra kết quả
                        </button>
                        <template x-if="tfChecked">
                            <button type="button" @click="resetTf()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-gray-300 hover:bg-slate-50 transition-colors">
                                Thử lại 🔄
                            </button>
                        </template>
                    </div>

                    <template x-if="tfChecked && tfFeedback">
                        <div class="p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-500/30 text-xs text-indigo-900 dark:text-indigo-200">
                            <span class="font-bold">💡 Phản hồi:</span> <span x-text="tfFeedback"></span>
                        </div>
                    </template>
                </div>
            </template>

            {{-- 6. QUESTION SET PLAYER --}}
            <template x-if="h5pType === 'question_set'">
                <div class="max-w-xl mx-auto space-y-5" x-data="{ qIdx: 0, userAns: {} }">
                    <div class="flex items-center justify-between text-xs text-slate-500 font-mono">
                        <span>Câu hỏi <strong class="text-sky-600" x-text="qIdx + 1"></strong> / <span x-text="h5pData.questions.length"></span></span>
                        <span>Đã làm: <strong x-text="Object.keys(userAns).length + ' câu'"></strong></span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white" x-text="h5pData.questions[qIdx]?.question"></h4>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(opt, optIdx) in (h5pData.questions[qIdx]?.options || [])" :key="optIdx">
                            <div class="p-3 rounded-xl border transition-all cursor-pointer flex items-center gap-3"
                                 :class="userAns[qIdx] === optIdx ? 'bg-sky-50 border-sky-300 text-sky-800 dark:bg-sky-950/30 dark:border-sky-500/50 dark:text-sky-300 font-bold' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-gray-300'"
                                 @click="userAns[qIdx] = optIdx">
                                <span class="w-6 h-6 rounded-lg border flex items-center justify-center text-xs font-mono font-bold"
                                      :class="userAns[qIdx] === optIdx ? 'bg-sky-600 text-white' : 'bg-slate-100 dark:bg-slate-700'"
                                      x-text="String.fromCharCode(65 + optIdx)"></span>
                                <span class="text-xs" x-text="opt"></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="if (qIdx > 0) qIdx--" :disabled="qIdx === 0"
                                class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold disabled:opacity-40">
                            ← Câu trước
                        </button>
                        <button type="button" @click="if (qIdx < h5pData.questions.length - 1) { qIdx++; } else { finishQuestionSet(userAns); }"
                                class="btn-primary !w-auto !py-1.5 px-4 text-xs font-bold shadow-glow-blue">
                            <span x-text="qIdx === h5pData.questions.length - 1 ? 'Hoàn thành bài tập ✓' : 'Câu kế tiếp →'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CASE 2: UPLOADED .H5P FILE PACKAGE (STANDALONE PLAYER)             --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @elseif($source === 'upload' && $extractedUrl)
        {{-- Load Local Player Assets (with CDN fallback) --}}
        <link rel="stylesheet" href="{{ asset('vendor/h5p-standalone/dist/styles/h5p.css') }}">
        <script src="{{ asset('vendor/h5p-standalone/dist/main.bundle.js') }}"></script>

        <style>
            .h5p-iframe-wrapper {
                margin: 0 auto !important;
                display: block !important;
                width: 100% !important;
                min-height: 560px !important;
                background: transparent !important;
            }
            .h5p-iframe {
                margin: 0 auto !important;
                display: block !important;
                width: 100% !important;
                min-height: 560px !important;
                border: none !important;
            }
        </style>

        <div class="rounded-2xl overflow-hidden border border-slate-700/80 shadow-2xl bg-slate-950/80 transition-all relative">
            <div id="h5p-loading-{{ $activity->id }}" 
                 class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-sm transition-opacity duration-300">
                <div class="w-9 h-9 border-2 border-sky-400 border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-3 text-xs text-sky-300 font-medium font-mono tracking-wide">Đang khởi chạy tương tác H5P...</p>
            </div>

            <div class="p-2 sm:p-4 min-h-[560px] flex flex-col justify-start">
                <div id="h5p-container-{{ $activity->id }}" class="w-full"></div>
            </div>
        </div>

        {{-- Footer Details & Download Link --}}
        <div class="flex flex-wrap items-center justify-between gap-2 px-1">
            <div class="flex items-center gap-2 text-[11px] text-gray-400 font-mono">
                <span class="inline-flex items-center gap-1 text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded-md border border-sky-500/20">
                    🧩 H5P Offline Package
                </span>
                @if($activity->file)
                    <span class="text-gray-500 hidden sm:inline">|</span>
                    <span class="text-gray-400 truncate max-w-xs">{{ $activity->file->original_name }}</span>
                    <span class="text-gray-500 text-[10px]">({{ $activity->file->getSizeFormatted() }})</span>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if($activity->file)
                    <a href="{{ $activity->file->getUrl() }}" download="{{ $activity->file->original_name }}"
                       class="text-[11px] text-sky-400/80 hover:text-sky-300 font-mono flex items-center gap-1 transition-colors">
                        <span>⬇️ Tải gói (.h5p)</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Initialize Standalone Player Script --}}
        <script>
        (function() {
            function injectCenterStyles(iframe) {
                if (!iframe) return;
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow?.document;
                    if (!doc) return;
                    if (!doc.getElementById('h5p-center-override')) {
                        const style = doc.createElement('style');
                        style.id = 'h5p-center-override';
                        style.textContent = `
                            html.h5p-iframe, html.h5p-iframe > body {
                                display: block !important;
                                margin: 0 !important;
                                padding: 0 !important;
                                width: 100% !important;
                                min-height: 100% !important;
                                height: auto !important;
                            }
                            .h5p-content {
                                margin: 0 auto !important;
                                display: block !important;
                                width: 100% !important;
                                height: auto !important;
                                box-sizing: border-box !important;
                            }
                            .boardgame, .h5p-boardgame, [class*="boardgame"], .h5p-content > div {
                                margin-left: auto !important;
                                margin-right: auto !important;
                            }
                            .boardgame.shadow {
                                margin: 0.75em auto !important;
                            }
                        `;
                        (doc.head || doc.body || doc.documentElement).appendChild(style);
                    }
                } catch(e) {}
            }

            function autoResizeIframe(iframe) {
                if (!iframe) return;
                try {
                    injectCenterStyles(iframe);
                    const doc = iframe.contentDocument || iframe.contentWindow?.document;
                    if (!doc) return;

                    const body = doc.body;
                    const html = doc.documentElement;
                    const contentEl = doc.querySelector('.h5p-content') || doc.querySelector('.boardgame') || body;

                    let cHeight = 0;
                    if (contentEl) {
                        cHeight = Math.max(
                            contentEl.scrollHeight || 0,
                            contentEl.offsetHeight || 0,
                            Math.round(contentEl.getBoundingClientRect().height || 0)
                        );
                    }

                    const measuredH = Math.max(
                        body ? body.scrollHeight : 0,
                        body ? body.offsetHeight : 0,
                        html ? html.scrollHeight : 0,
                        cHeight
                    );

                    const finalH = Math.max(measuredH + 25, 560);
                    iframe.style.height = finalH + 'px';
                    const wrapper = iframe.closest('.h5p-iframe-wrapper');
                    if (wrapper) {
                        wrapper.style.height = finalH + 'px';
                    }
                } catch(e) {}
            }

            function initH5pPlayer() {
                const el = document.getElementById('h5p-container-{{ $activity->id }}');
                const loader = document.getElementById('h5p-loading-{{ $activity->id }}');
                if (!el) return;

                const hideLoader = () => {
                    if (loader) {
                        loader.style.opacity = '0';
                        setTimeout(() => loader.remove(), 350);
                    }
                };

                const observer = new MutationObserver(() => {
                    const iframe = el.querySelector('iframe');
                    if (iframe) {
                        injectCenterStyles(iframe);
                        autoResizeIframe(iframe);

                        iframe.addEventListener('load', () => {
                            injectCenterStyles(iframe);
                            autoResizeIframe(iframe);
                            setTimeout(() => {
                                injectCenterStyles(iframe);
                                autoResizeIframe(iframe);
                                hideLoader();
                            }, 300);
                        });

                        try {
                            const innerDoc = iframe.contentDocument || iframe.contentWindow?.document;
                            if (innerDoc) {
                                const innerObserver = new MutationObserver(() => autoResizeIframe(iframe));
                                innerObserver.observe(innerDoc.body || innerDoc.documentElement, {
                                    childList: true,
                                    subtree: true,
                                    attributes: true,
                                    characterData: true
                                });
                            }
                        } catch(e) {}
                    }
                });

                observer.observe(el, { childList: true, subtree: true });
                const safetyTimer = setTimeout(() => {
                    hideLoader();
                    const iframe = el.querySelector('iframe');
                    if (iframe) autoResizeIframe(iframe);
                }, 4000);

                if (typeof H5PStandalone === 'undefined') {
                    console.warn('H5PStandalone not found locally, loading CDN fallback...');
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/h5p-standalone@3.6.0/dist/main.bundle.js';
                    script.onload = () => runH5P(el, hideLoader, safetyTimer, observer);
                    script.onerror = () => {
                        clearTimeout(safetyTimer);
                        hideLoader();
                        el.innerHTML = '<div class="p-8 text-center text-rose-400">Không thể tải trình phát H5P.</div>';
                    };
                    document.head.appendChild(script);
                } else {
                    runH5P(el, hideLoader, safetyTimer, observer);
                }
            }

            function runH5P(el, hideLoader, safetyTimer, observer) {
                new H5PStandalone.H5P(el, {
                    h5pJsonPath: '{{ $extractedUrl }}',
                    frameCss: '{{ asset("vendor/h5p-standalone/dist/styles/h5p.css") }}',
                    frameJs: '{{ asset("vendor/h5p-standalone/dist/main.bundle.js") }}'
                })
                    .then(() => {
                        const iframe = el.querySelector('iframe');
                        if (iframe) {
                            autoResizeIframe(iframe);
                            setTimeout(() => {
                                autoResizeIframe(iframe);
                                hideLoader();
                            }, 300);
                        } else {
                            hideLoader();
                        }

                        if (window.H5P && window.H5P.externalDispatcher) {
                            window.H5P.externalDispatcher.on('xAPI', function (event) {
                                const statement = event.data ? event.data.statement : null;
                                if (!statement) return;

                                const verbId = statement.verb?.id || '';
                                const verbDisplay = (statement.verb?.display?.['en-US'] || '').toLowerCase();

                                if (verbDisplay === 'completed' || verbId.endsWith('/completed') ||
                                    verbDisplay === 'passed' || verbId.endsWith('/passed') ||
                                    verbDisplay === 'answered' || verbId.endsWith('/answered')) {

                                    let score = 100;
                                    if (statement.result?.score?.scaled !== undefined) {
                                        score = Math.round(statement.result.score.scaled * 100);
                                    } else if (statement.result?.score?.raw !== undefined && statement.result?.score?.max) {
                                        score = Math.round((statement.result.score.raw / statement.result.score.max) * 100);
                                    }

                                    const alpineEl = document.querySelector('[x-data*="activityTelemetry"]');
                                    if (alpineEl && window.Alpine) {
                                        try {
                                            const data = Alpine.$data(alpineEl);
                                            if (data && typeof data.markCompleted === 'function') {
                                                data.markCompleted(score);
                                            }
                                        } catch (e) {
                                            console.log('Alpine telemetry auto-complete:', e);
                                        }
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => {
                        clearTimeout(safetyTimer);
                        observer.disconnect();
                        hideLoader();
                        console.error('H5P initialization failed:', err);
                        const errMsg = (err && err.message) ? err.message : 'Lỗi khởi chạy gói H5P';
                        el.innerHTML = `
                            <div class="p-8 text-center space-y-3">
                                <div class="text-3xl">⚠️</div>
                                <div class="text-xs font-bold text-rose-300">Không thể tải nội dung gói H5P</div>
                                <div class="text-[11px] text-gray-300 font-mono bg-slate-900/90 p-3 rounded-xl border border-slate-800 max-w-lg mx-auto text-left leading-relaxed">
                                    <div class="text-rose-400 font-semibold mb-1">Chi tiết: ${errMsg}</div>
                                    <div class="text-gray-400 text-[10px]">
                                        Mẹo: Khi xuất file từ Lumi hoặc phần mềm tạo H5P, hãy chọn "Bao gồm tất cả thư viện" trước khi lưu tệp .h5p.
                                    </div>
                                </div>
                                <button type="button" onclick="location.reload()" class="px-4 py-1.5 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/40 text-xs font-semibold transition-all">
                                    Tải lại trang ⟳
                                </button>
                            </div>
                        `;
                    });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initH5pPlayer);
            } else {
                initH5pPlayer();
            }
        })();
        </script>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CASE 3: URL EMBED H5P                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @elseif($hasEmbedUrl)
        @php
            $ratio = $content['aspect_ratio'] ?? '16:9';
            $paddingMap = [
                '16:9' => '56.25%',
                '4:3'  => '75%',
                '1:1'  => '100%',
                '9:16' => '177.78%',
            ];
            $padding = $paddingMap[$ratio] ?? '56.25%';
        @endphp
        <div class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700/80 shadow-lg bg-white dark:bg-slate-900">
            <div class="relative w-full" style="padding-bottom: {{ $padding }};">
                <iframe src="{{ $content['embed_url'] }}"
                        class="absolute inset-0 w-full h-full"
                        allowfullscreen
                        allow="fullscreen; autoplay; encrypted-media"
                        frameborder="0"
                        loading="lazy"
                        style="border: none;">
                </iframe>
            </div>
        </div>

        <div class="flex items-center justify-between px-1">
            <span class="text-[10px] text-gray-500 font-mono flex items-center gap-1">
                🧩 Nội dung H5P nhúng từ URL
            </span>
            <a href="{{ $content['embed_url'] }}" target="_blank" rel="noopener"
               class="text-[10px] text-sky-500 dark:text-sky-400/60 hover:text-sky-600 dark:hover:text-sky-300 transition-colors font-mono flex items-center gap-1">
                Mở trong tab mới ↗
            </a>
        </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CASE 4: EMPTY STATE                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @else
        <div class="p-8 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-center space-y-3">
            <span class="text-4xl block">🧩</span>
            <p class="text-xs text-slate-500 dark:text-gray-400">Chưa có nội dung hoặc tệp .h5p được cấu hình cho hoạt động này.</p>
        </div>
    @endif

    {{-- Completion Button --}}
    @include('activities._completion-button', ['label' => 'Đã hoàn thành hoạt động H5P ✓'])
</div>
