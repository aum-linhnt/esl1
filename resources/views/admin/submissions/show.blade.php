@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.submissions.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-white mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại danh sách bài nộp
            </a>
            <h1 class="text-2xl font-bold text-white tracking-tight">Chi tiết Bài nộp #{{ $submission->id }}</h1>
        </div>

        <form method="POST" action="{{ route('admin.submissions.destroy', $submission->id) }}" onsubmit="return confirm('Xác nhận xóa bài nộp này?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs bg-red-500/15 text-red-400 hover:bg-red-500/25 px-3 py-1.5 rounded-lg font-semibold transition-colors">
                Xóa bài nộp
            </button>
        </form>
    </div>

    {{-- Submission Info Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Học viên</span>
            <p class="text-sm font-bold text-white mt-1">{{ $submission->user->name ?? 'User #' . $submission->user_id }}</p>
            <span class="text-xs text-fsel-teal font-mono">{{ $submission->user->email ?? $submission->user->username ?? '' }}</span>
        </div>

        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Loại bài thi & Kết quả</span>
            <p class="text-sm font-bold text-indigo-400 uppercase mt-1">{{ str_replace('_', ' ', $submission->test_type) }}</p>
            <span class="text-xs {{ $submission->is_passed ? 'text-emerald-400' : 'text-red-400' }} font-bold">
                {{ $submission->is_passed ? '✓ Đạt (Pass)' : '✕ Chưa đạt (Fail)' }}
            </span>
        </div>

        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Lần thi & Thời gian</span>
            <p class="text-sm font-bold text-indigo-300 font-mono mt-1">Lần làm #{{ $submission->attempt_number ?? 1 }}</p>
            <span class="text-xs text-gray-400 font-mono">
                @if($submission->time_spent_seconds)
                    ⏱️ {{ floor($submission->time_spent_seconds / 60) }}m {{ $submission->time_spent_seconds % 60 }}s
                @else
                    {{ $submission->created_at->format('d/m/Y H:i') }}
                @endif
            </span>
        </div>

        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Điểm & Độ chính xác</span>
            <p class="text-xl font-black text-white mt-1 font-mono">{{ $submission->total_score }}/{{ $submission->max_score }}</p>
            <span class="text-xs text-fsel-teal font-mono font-bold">{{ $submission->accuracy_rate }}% Accuracy</span>
        </div>
    </div>

    @php
        $payload = $submission->answers_payload;
    @endphp

    {{-- Question Breakdown Table (if available) --}}
    @if(is_array($payload) && !empty($payload['details']))
        <div class="admin-card p-6 space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center justify-between">
                <span class="flex items-center gap-2">📝 Chi tiết Từng Câu hỏi (Question Breakdown & Versioning)</span>
                <span class="text-xs text-gray-400 font-normal">Tổng: {{ count($payload['details']) }} câu hỏi</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-slate-900/80 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Nội dung câu hỏi</th>
                            <th class="px-4 py-3">Version</th>
                            <th class="px-4 py-3">Đáp án của học viên</th>
                            <th class="px-4 py-3">Đáp án đúng</th>
                            <th class="px-4 py-3 text-right">Kết quả</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($payload['details'] as $idx => $q)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-gray-400">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="text-white font-medium line-clamp-2">{{ $q['question_text'] ?? 'Câu hỏi #' . ($q['question_id'] ?? $idx + 1) }}</div>
                                    @if(!empty($q['explanation']))
                                        <div class="text-[10px] text-gray-500 mt-1 line-clamp-1 italic">💡 {{ $q['explanation'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold bg-slate-800 text-cyan-300 border border-cyan-500/30">
                                        v{{ $q['version'] ?? 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold {{ !empty($q['is_correct']) ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $q['user_answer'] ?: '(Trống)' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-emerald-400">
                                    {{ $q['correct_answer'] ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if(!empty($q['is_correct']))
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            ✓ Đúng
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                                            ✕ Sai
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Payload Inspector Card --}}
    <div class="admin-card p-6 space-y-4" x-data="{ expanded: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🔍 Dữ liệu gốc Payload (Raw JSON)</span>
            </h3>
            <button type="button" class="text-xs text-indigo-400 hover:text-white flex items-center gap-1 font-mono">
                <span x-text="expanded ? 'Thu gọn ▲' : 'Mở rộng ▼'"></span>
            </button>
        </div>

        <div x-show="expanded" x-cloak>
            @if(is_array($payload))
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-xs font-mono text-gray-300 overflow-x-auto max-h-96">
                    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @else
                <p class="text-xs text-gray-400 italic">Không có dữ liệu chi tiết kèm theo.</p>
            @endif
        </div>
    </div>
</div>
@endsection
