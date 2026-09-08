{{-- Metrics Dashboard Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
    {{-- Total Questions --}}
    <div class="card-dark p-4 border-slate-800 flex items-center gap-3 relative overflow-hidden">
        <div class="w-10 h-10 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg flex-shrink-0">
            📚
        </div>
        <div>
            <span class="text-[11px] text-gray-400 block font-medium">Tổng câu hỏi</span>
            <span class="text-lg font-black text-white font-mono">{{ number_format($stats['total']) }}</span>
        </div>
    </div>

    {{-- Listening --}}
    <div class="card-dark p-4 border-slate-800 flex items-center gap-3 relative overflow-hidden">
        <div class="w-10 h-10 rounded-xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg flex-shrink-0">
            🎧
        </div>
        <div>
            <span class="text-[11px] text-gray-400 block font-medium">Kỹ năng Nghe</span>
            <span class="text-lg font-black text-emerald-400 font-mono">{{ number_format($stats['listening']) }}</span>
        </div>
    </div>

    {{-- Reading --}}
    <div class="card-dark p-4 border-slate-800 flex items-center gap-3 relative overflow-hidden">
        <div class="w-10 h-10 rounded-xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center text-lg flex-shrink-0">
            📖
        </div>
        <div>
            <span class="text-[11px] text-gray-400 block font-medium">Kỹ năng Đọc</span>
            <span class="text-lg font-black text-purple-400 font-mono">{{ number_format($stats['reading']) }}</span>
        </div>
    </div>

    {{-- Writing --}}
    <div class="card-dark p-4 border-slate-800 flex items-center gap-3 relative overflow-hidden">
        <div class="w-10 h-10 rounded-xl bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center text-lg flex-shrink-0">
            ✍️
        </div>
        <div>
            <span class="text-[11px] text-gray-400 block font-medium">Kỹ năng Viết</span>
            <span class="text-lg font-black text-cyan-400 font-mono">{{ number_format($stats['writing']) }}</span>
        </div>
    </div>

    {{-- Speaking --}}
    <div class="card-dark p-4 border-slate-800 flex items-center gap-3 relative overflow-hidden">
        <div class="w-10 h-10 rounded-xl bg-amber-600/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-lg flex-shrink-0">
            🎙️
        </div>
        <div>
            <span class="text-[11px] text-gray-400 block font-medium">Kỹ năng Nói</span>
            <span class="text-lg font-black text-amber-400 font-mono">{{ number_format($stats['speaking']) }}</span>
        </div>
    </div>

    {{-- Testlet Clusters --}}
    <div class="card-dark p-4 border-purple-800/40 bg-purple-950/20 flex items-center gap-3 relative overflow-hidden">
        <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/40 flex items-center justify-center text-lg flex-shrink-0">
            📦
        </div>
        <div>
            <span class="text-[11px] text-purple-300 block font-bold">Cụm Testlet</span>
            <span class="text-lg font-black text-purple-200 font-mono">{{ number_format($stats['testlets']) }}</span>
        </div>
    </div>
</div>
