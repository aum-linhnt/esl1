@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="adminQuestionHub()">
    
    {{-- 1. TOP HEADER & STUDIO ACTION BUTTONS --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span>📚</span>
                <span>Ngân Hàng Câu Hỏi & Quản Trị Cụm Testlet</span>
            </h1>
            <p class="text-xs text-gray-400 mt-1">
                Biên soạn, phân loại, kiểm soát chất lượng câu hỏi 4 Kỹ năng (Nghe, Đọc, Viết, Nói) và quản lý các Cụm bài đọc học thuật
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Toggle Single Question Studio --}}
            <button type="button" 
                    @click="toggleSingleCreate()"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-md"
                    :class="openSingleCreate ? 'bg-indigo-600 text-white shadow-indigo-500/30' : 'bg-slate-800 text-indigo-300 hover:bg-slate-700 border border-indigo-500/30'">
                <span>➕</span>
                <span>Thêm câu hỏi lẻ</span>
            </button>

            {{-- Toggle Testlet Studio --}}
            <button type="button" 
                    @click="toggleTestletCreate()"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-md"
                    :class="openTestletCreate ? 'bg-purple-600 text-white shadow-purple-500/30' : 'bg-purple-900/30 text-purple-300 hover:bg-purple-900/50 border border-purple-500/40'">
                <span>📦</span>
                <span>Biên Soạn Cụm Bài Đọc (Testlet)</span>
            </button>

            {{-- AI Generator Link --}}
            <a href="{{ route('teacher.ai_generator.index') }}" 
               class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-600/30 to-purple-600/30 text-amber-300 border border-amber-500/30 hover:brightness-125 text-xs font-bold transition-all flex items-center gap-2">
                <span>🪄</span>
                <span>Sinh đề AI Gemini</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-2">
                <span class="text-base">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-500/15 border border-red-500/30 text-red-300 text-xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-white">&times;</button>
        </div>
    @endif

    {{-- 2. METRICS DASHBOARD CARDS (MODULAR) --}}
    @include('admin.questions.partials.stats')

    {{-- 3. STUDIO FORM 1: BIÊN SOẠN CÂU HỎI ĐƠN LẺ (MODULAR) --}}
    @include('admin.questions.partials.single-studio')

    {{-- 4. STUDIO FORM 2: BIÊN SOẠN CỤM BÀI ĐỌC (TESTLET STUDIO - MODULAR) --}}
    @include('admin.questions.partials.testlet-studio')

    {{-- 5. INTERACTIVE TAB NAVIGATION (All Questions vs Testlets) --}}
    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
        <div class="flex items-center gap-2">
            {{-- Tab 1: All Questions --}}
            <a href="{{ route('admin.questions.index', ['tab' => 'questions', 'skill' => $skill, 'difficulty' => $difficulty, 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
               :class="'{{ $currentTab }}' === 'questions' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-gray-400 hover:text-white bg-slate-900/60'">
                <span>📋</span>
                <span>Tất Cả Câu Hỏi</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-blue-900/40 text-blue-200">
                    {{ $questions->total() }}
                </span>
            </a>

            {{-- Tab 2: Testlet Clusters --}}
            <a href="{{ route('admin.questions.index', ['tab' => 'testlets']) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
               :class="'{{ $currentTab }}' === 'testlets' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'text-gray-400 hover:text-white bg-slate-900/60'">
                <span>📚</span>
                <span>Quản Lý Cụm Bài Đọc (Testlets)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-purple-900/40 text-purple-200">
                    {{ count($testletGroups) }} Cụm
                </span>
            </a>
        </div>
    </div>

    {{-- ─── TAB 1: ALL QUESTIONS LIST WITH ADVANCED FILTER TOOLBAR ─────── --}}
    @if($currentTab === 'questions')
        @include('admin.questions.partials.filters')
        @include('admin.questions.partials.question-table')
    @endif

    {{-- ─── TAB 2: QUẢN LÝ CỤM BÀI ĐỌC (TESTLET CLUSTERS) ─────────────── --}}
    @if($currentTab === 'testlets')
        @include('admin.questions.partials.testlet-list')
    @endif

    {{-- ─── 6. MODAL PREVIEW QUESTION (MODULAR) ────────────────────────── --}}
    @include('admin.questions.partials.modal-preview')

    {{-- ─── 7. MODAL EDIT SINGLE QUESTION (MODULAR) ───────────────────── --}}
    @include('admin.questions.partials.modal-edit-question')

    {{-- ─── 8. MODAL EDIT TESTLET PASSAGE (MODULAR) ───────────────────── --}}
    @include('admin.questions.partials.modal-edit-testlet')

</div>

{{-- Alpine.js State Controller for Admin Question Hub --}}
<script>
function adminQuestionHub() {
    return {
        openSingleCreate: false,
        openTestletCreate: false,
        singleSkill: 'reading',
        singleQType: 'mcq',

        // Bulk Selection & Deletion State
        selectedQuestions: [],
        pageQuestionIds: @json($questions->pluck('id')),
        bulkDeleting: false,

        get isAllSelected() {
            return this.pageQuestionIds.length > 0 && this.pageQuestionIds.every(id => this.selectedQuestions.includes(id));
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.pageQuestionIds.forEach(id => {
                    if (!this.selectedQuestions.includes(id)) {
                        this.selectedQuestions.push(id);
                    }
                });
            } else {
                this.selectedQuestions = this.selectedQuestions.filter(id => !this.pageQuestionIds.includes(id));
            }
        },

        clearSelection() {
            this.selectedQuestions = [];
        },

        async executeBulkDelete() {
            if (!this.selectedQuestions.length || this.bulkDeleting) return;
            const count = this.selectedQuestions.length;
            if (!confirm(`Bạn có chắc chắn muốn xóa vĩnh viễn ${count} câu hỏi đã chọn? Thao tác này không thể hoàn tác!`)) {
                return;
            }

            this.bulkDeleting = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch('{{ route("admin.questions.bulkDestroy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ ids: this.selectedQuestions })
                });

                const data = await res.json();
                this.bulkDeleting = false;

                if (res.ok && data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Lỗi khi xóa hàng loạt.');
                }
            } catch (err) {
                this.bulkDeleting = false;
                console.error(err);
                alert('Lỗi kết nối khi xóa hàng loạt.');
            }
        },

        // Testlet Studio Array
        testletQuestions: [
            { question_text: '', opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct_answer: 'A', explanation: '' },
            { question_text: '', opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct_answer: 'B', explanation: '' },
            { question_text: '', opt_a: '', opt_b: '', opt_c: '', opt_d: '', correct_answer: 'C', explanation: '' }
        ],

        // Modals State
        previewModal: {
            open: false,
            data: {}
        },
        editModal: {
            open: false,
            data: {},
            optionsText: ''
        },
        editTestletModal: {
            open: false,
            oldTitle: '',
            title: '',
            content: '',
            difficulty: 'B2'
        },

        toggleSingleCreate() {
            this.openSingleCreate = !this.openSingleCreate;
            if (this.openSingleCreate) this.openTestletCreate = false;
        },

        toggleTestletCreate() {
            this.openTestletCreate = !this.openTestletCreate;
            if (this.openTestletCreate) this.openSingleCreate = false;
        },

        addTestletQuestion() {
            this.testletQuestions.push({
                question_text: '',
                opt_a: '',
                opt_b: '',
                opt_c: '',
                opt_d: '',
                correct_answer: 'A',
                explanation: ''
            });
        },

        removeTestletQuestion(index) {
            if (this.testletQuestions.length > 1) {
                this.testletQuestions.splice(index, 1);
            }
        },

        async openPreviewModal(questionId) {
            try {
                const res = await fetch(`/admin/questions/${questionId}/json`);
                if (res.ok) {
                    this.previewModal.data = await res.json();
                    this.previewModal.open = true;
                }
            } catch (err) {
                console.error('Failed to load question details', err);
            }
        },

        async openEditModal(questionId) {
            try {
                const res = await fetch(`/admin/questions/${questionId}/json`);
                if (res.ok) {
                    const data = await res.json();
                    this.editModal.data = data;
                    if (Array.isArray(data.options)) {
                        this.editModal.optionsText = data.options.map(opt => typeof opt === 'string' ? opt : JSON.stringify(opt)).join('\n');
                    } else {
                        this.editModal.optionsText = '';
                    }
                    this.editModal.open = true;
                }
            } catch (err) {
                console.error('Failed to load question for edit', err);
            }
        },

        openEditTestletModal(title, content, difficulty) {
            this.editTestletModal.oldTitle = title;
            this.editTestletModal.title = title;
            this.editTestletModal.content = content;
            this.editTestletModal.difficulty = difficulty.split(',')[0].trim() || 'B2';
            this.editTestletModal.open = true;
        },

        isOptionCorrect(opt, correctAnswer) {
            if (!correctAnswer || !opt) return false;
            const optStr = (typeof opt === 'string' ? opt : JSON.stringify(opt)).trim().toLowerCase();
            const corStr = String(correctAnswer).trim().toLowerCase();
            return optStr === corStr || optStr.startsWith(corStr + '.') || corStr.startsWith(optStr + '.');
        }
    };
}
</script>
@endsection
