@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Cấu hình Hệ thống LMS & AI Engine</h1>
            <p class="text-xs text-gray-400">Quản lý API Key Gemini AI, tham số vận hành và bộ nhớ đệm hệ thống</p>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────── --}}
    {{-- 1. GEMINI AI API KEY CONFIGURATION --}}
    {{-- ───────────────────────────────────────────────────────── --}}
    <div class="admin-card p-6 space-y-5 {{ $isConfigured ? 'border-emerald-500/30' : 'border-amber-500/30' }}">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>🤖 Cấu hình Google Gemini AI</span>
            </h3>
            <span class="gemini-status-badge {{ $isConfigured ? 'status-configured' : 'status-fallback' }} inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-mono font-semibold border {{ $isConfigured ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' : 'text-amber-400 bg-amber-500/10 border-amber-500/20' }}">
                <span class="w-2 h-2 rounded-full {{ $isConfigured ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse' }}"></span>
                {{ $geminiStatus }}
            </span>
        </div>

        {{-- API Key Input Form --}}
        <form method="POST" action="{{ route('admin.settings.updateApiKey') }}" class="space-y-4" x-data="{ showKey: false, testing: false, testResult: null }">
            @csrf
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-gray-300">Google Gemini API Key</label>
                <div class="flex gap-3">
                    <div class="flex-1 relative">
                        <input
                            :type="showKey ? 'text' : 'password'"
                            name="api_key"
                            value="{{ $maskedKey }}"
                            placeholder="Nhập API Key từ Google AI Studio..."
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white font-mono placeholder-gray-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition-all">
                        <button type="button" @click="showKey = !showKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors">
                            <svg x-show="!showKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showKey" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    <button type="submit" class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors flex items-center gap-2 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Lưu API Key</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 mb-1">Gemini Flash Model (Tutor & Sinh Đề):</label>
                        <input
                            type="text"
                            name="model_flash"
                            value="{{ $systemSettings['gemini_model_flash'] }}"
                            placeholder="gemini-3.6-flash"
                            class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white font-mono placeholder-gray-600 focus:outline-none focus:border-indigo-500">
                        <span class="text-[10px] text-gray-500 mt-0.5 block">Khuyên dùng: <code>gemini-3.6-flash</code> hoặc <code>gemini-2.0-flash</code></span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 mb-1">Gemini Pro Model (IELTS Writing & Speaking):</label>
                        <input
                            type="text"
                            name="model_pro"
                            value="{{ $systemSettings['gemini_model_pro'] }}"
                            placeholder="gemini-3.6-pro"
                            class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white font-mono placeholder-gray-600 focus:outline-none focus:border-indigo-500">
                        <span class="text-[10px] text-gray-500 mt-0.5 block">Khuyên dùng: <code>gemini-3.6-pro</code> hoặc <code>gemini-3.6-flash</code></span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <p class="text-[11px] text-gray-500">
                        Lấy API Key miễn phí tại: <a href="https://aistudio.google.com/apikey" target="_blank" class="text-indigo-400 hover:text-indigo-300 underline">Google AI Studio</a>
                        · Free tier: 15 requests/phút
                    </p>
                    
                    {{-- Test Connection Button --}}
                    <button
                        type="button"
                        @click="testing = true; testResult = null; fetch('{{ route('admin.settings.testConnection') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(r => r.json()).then(d => { testing = false; testResult = d; }).catch(() => { testing = false; testResult = { success: false, message: 'Lỗi mạng' }; })"
                        :disabled="testing"
                        class="text-xs font-bold px-3 py-1.5 rounded-lg border transition-all flex items-center gap-1.5 {{ $isConfigured ? 'text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/10' : 'text-amber-400 border-amber-500/30 hover:bg-amber-500/10' }}">
                        <span x-show="!testing">🔌 Test kết nối</span>
                        <span x-show="testing" class="flex items-center gap-1.5">
                            <span class="w-3 h-3 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                            Đang kiểm tra...
                        </span>
                    </button>
                </div>

                {{-- Test Result Display --}}
                <div x-show="testResult" x-cloak class="p-3 rounded-xl text-xs font-mono border transition-all"
                     :class="testResult?.success ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' : 'bg-red-500/10 border-red-500/30 text-red-300'">
                    <span x-text="testResult?.success ? '✅ ' + testResult.message : '❌ ' + testResult.message"></span>
                </div>
            </div>
        </form>

        {{-- Model & Config Info --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2 border-t border-slate-800">
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1">
                <span class="text-gray-400">Flash Model (Chat & Generator):</span>
                <p class="text-white font-mono font-bold">{{ $systemSettings['gemini_model_flash'] }}</p>
                <span class="text-[10px] text-gray-500 font-mono">temp: {{ $systemSettings['gemini_temp_flash'] }}</span>
            </div>
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1">
                <span class="text-gray-400">Pro Model (Writing & Speaking):</span>
                <p class="text-white font-mono font-bold">{{ $systemSettings['gemini_model_pro'] }}</p>
                <span class="text-[10px] text-gray-500 font-mono">temp: {{ $systemSettings['gemini_temp_pro'] }}</span>
            </div>
            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1">
                <span class="text-gray-400">Rate Limit (Free Tier):</span>
                <p class="text-white font-mono font-bold">{{ $systemSettings['gemini_max_rpm'] }} RPM</p>
                <span class="text-[10px] text-gray-500 font-mono">15 requests/phút</span>
            </div>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────── --}}
    {{-- 2. SYSTEM THEME & COLOR PALETTE BRANDING --}}
    {{-- ───────────────────────────────────────────────────────── --}}
    <div class="admin-card p-6 space-y-5 border border-slate-700/60" 
         x-data="{
             selectedMode: '{{ $themeConfig['mode'] }}',
             selectedAccent: '{{ $themeConfig['accent'] }}',
             enableGlow: {{ $themeConfig['glow'] ? 'true' : 'false' }},
             palettes: {{ json_encode($themeConfig['palettes']) }},
             get currentPalette() {
                 return this.palettes[this.selectedAccent] || this.palettes['indigo'];
             }
         }">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span>🎨 Cấu hình Giao diện & Màu sắc Mặc định (Theme & Branding)</span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">Thiết lập chế độ hiển thị, màu nhận diện thương hiệu và hiệu ứng Neon Glow mặc định toàn hệ thống</p>
            </div>
            <span class="theme-palettes-badge text-xs px-2.5 py-1 rounded-full font-mono font-semibold bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 self-start sm:self-auto">
                7 Palettes Chuẩn
            </span>
        </div>

        <form method="POST" action="{{ route('admin.settings.updateTheme') }}" class="space-y-6">
            @csrf

            {{-- 1. Mode Selection --}}
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-2">1. Chế độ hiển thị mặc định (Appearance Mode):</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Dark Mode --}}
                    <label class="theme-mode-card relative flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                           :class="selectedMode === 'dark' ? 'active' : ''">
                        <input type="radio" name="theme_mode" value="dark" x-model="selectedMode" class="sr-only">
                        <div class="theme-mode-icon-box w-8 h-8 rounded-lg flex items-center justify-center text-amber-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold">Giao diện Tối (Dark)</p>
                            <p class="text-[10px] opacity-70">Nền đêm hiện đại, bảo vệ mắt</p>
                        </div>
                    </label>

                    {{-- Light Mode --}}
                    <label class="theme-mode-card relative flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                           :class="selectedMode === 'light' ? 'active' : ''">
                        <input type="radio" name="theme_mode" value="light" x-model="selectedMode" class="sr-only">
                        <div class="theme-mode-icon-box w-8 h-8 rounded-lg flex items-center justify-center text-amber-500 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold">Giao diện Sáng (Light)</p>
                            <p class="text-[10px] opacity-70">Nền trắng tinh gọn, thanh lịch</p>
                        </div>
                    </label>

                    {{-- Auto Mode --}}
                    <label class="theme-mode-card relative flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                           :class="selectedMode === 'auto' ? 'active' : ''">
                        <input type="radio" name="theme_mode" value="auto" x-model="selectedMode" class="sr-only">
                        <div class="theme-mode-icon-box w-8 h-8 rounded-lg flex items-center justify-center text-cyan-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold">Hệ thống (Auto)</p>
                            <p class="text-[10px] opacity-70">Tự động theo OS người dùng</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 2. Accent Palette Selection --}}
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-2">2. Bảng màu chủ đạo (Primary Accent Brand Color):</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2.5">
                    @foreach($themeConfig['palettes'] as $key => $palette)
                        <label class="palette-swatch-card relative flex flex-col items-center p-2.5 rounded-xl border cursor-pointer transition-all text-center group"
                               :class="selectedAccent === '{{ $key }}' ? 'active scale-[1.03]' : ''">
                            <input type="radio" name="theme_accent" value="{{ $key }}" x-model="selectedAccent" class="sr-only">
                            
                            {{-- Color Circle Swatch --}}
                            <div class="w-8 h-8 rounded-xl shadow-md flex items-center justify-center mb-1.5 transition-transform group-hover:scale-105"
                                 style="background: {{ $palette['gradient'] }};">
                                <svg x-show="selectedAccent === '{{ $key }}'" class="w-4 h-4 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>

                            <span class="text-[11px] font-bold block truncate w-full">{{ $palette['name'] }}</span>
                            <span class="text-[9px] font-mono opacity-70 uppercase">{{ $palette['primary'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 3. Neon Glow Effect Toggle --}}
            <div class="flex items-center justify-between p-3.5 rounded-xl border"
                 :class="selectedMode === 'light' ? 'bg-white border-slate-200' : 'bg-slate-900/60 border-slate-800'">
                <div class="flex items-center gap-3">
                    <span class="text-xl">✨</span>
                    <div>
                        <p class="text-xs font-bold" :class="selectedMode === 'light' ? 'text-slate-900' : 'text-white'">Hiệu ứng Neon Glow (Ambient Lighting)</p>
                        <p class="text-[10px]" :class="selectedMode === 'light' ? 'text-slate-500' : 'text-gray-400'">Hiển thị viền sáng mờ ảo quanh nút bấm chính và thẻ trạng thái</p>
                    </div>
                </div>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="theme_glow" value="1" x-model="enableGlow" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            {{-- 4. Live Interactive Preview Box --}}
            <div class="p-4 rounded-xl border space-y-3 transition-colors duration-300"
                 :class="selectedMode === 'light' ? 'bg-white border-slate-200 text-slate-900 shadow-sm' : 'bg-slate-950 border-slate-800 text-white'">
                <div class="flex items-center justify-between text-xs pb-2 border-b"
                     :class="selectedMode === 'light' ? 'border-slate-200' : 'border-slate-800'">
                    <span class="font-bold flex items-center gap-1.5">
                        <span>👁️ Live Preview Swatch</span>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded font-bold"
                              :class="selectedMode === 'light' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-800 text-slate-300'"
                              x-text="currentPalette.name + ' (' + selectedMode.toUpperCase() + ')'"></span>
                    </span>
                    <span class="text-[10px]" :class="selectedMode === 'light' ? 'text-slate-500' : 'text-slate-400'">Mô phỏng tức thì</span>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    {{-- Simulated Primary Button --}}
                    <button type="button" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-md"
                            :style="'background: ' + currentPalette.gradient + '; box-shadow: 0 0 16px ' + (enableGlow ? currentPalette.glow : 'transparent')">
                        Nút bấm chính (Button)
                    </button>

                    {{-- Simulated Active Pill --}}
                    <div class="px-3 py-1.5 rounded-lg border text-xs font-bold flex items-center gap-2"
                         :style="'border-color: ' + currentPalette.primary + '; color: ' + currentPalette.primary + '; background: ' + (selectedMode === 'light' ? '#ffffff' : 'rgba(15, 23, 42, 0.8)')">
                        <span class="w-2 h-2 rounded-full animate-pulse" :style="'background-color: ' + currentPalette.primary"></span>
                        <span>Trạng thái kích hoạt</span>
                    </div>

                    {{-- Simulated Badge --}}
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold text-white shadow-sm"
                          :style="'background-color: ' + currentPalette.primary">
                        +150 XP
                    </span>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex items-center justify-end pt-2">
                <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-indigo-600/20 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Lưu Cấu hình Giao diện Mặc định</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ───────────────────────────────────────────────────────── --}}
    {{-- 3. SYSTEM PARAMETERS --}}
    {{-- ───────────────────────────────────────────────────────── --}}
    <div class="admin-card p-6 space-y-4">
        <h3 class="text-base font-bold text-white">⚙️ Tham số Vận hành Đào tạo & Gamification XP</h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50 space-y-1">
                <span class="text-gray-400">Thời gian học thử mặc định:</span>
                <p class="text-base font-bold text-white">{{ $systemSettings['trial_days'] }} ngày</p>
            </div>

            <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50 space-y-1">
                <span class="text-gray-400">XP ban đầu khi đăng ký:</span>
                <p class="text-base font-bold text-teal-300">⚡ {{ $systemSettings['default_xp'] }} XP</p>
            </div>

            <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50 space-y-1">
                <span class="text-gray-400">Điểm tối thiểu qua bài (Pass):</span>
                <p class="text-base font-bold text-emerald-400">≥ {{ $systemSettings['quiz_pass_rate'] }}% chính xác</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50 space-y-1">
                <span class="text-gray-400">XP thưởng khi nộp bài Writing AI:</span>
                <p class="text-base font-bold text-teal-300">⚡ +{{ $systemSettings['ai_writing_xp'] }} XP / lần chấm</p>
            </div>
            <div class="bg-slate-800/40 p-4 rounded-xl border border-slate-700/50 space-y-1">
                <span class="text-gray-400">XP thưởng khi luyện Speaking AI:</span>
                <p class="text-base font-bold text-teal-300">⚡ +{{ $systemSettings['ai_speaking_xp'] }} XP / lần phát âm</p>
            </div>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────── --}}
    {{-- 3. CACHE MANAGEMENT --}}
    {{-- ───────────────────────────────────────────────────────── --}}
    <div class="admin-card p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">🧹 Dọn dẹp Bộ nhớ đệm (Cache & View)</h3>
                <p class="text-xs text-gray-400 mt-0.5">Xóa cache ứng dụng, route, config và compiled view khi cập nhật giao diện hoặc cấu hình</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.clearCache') }}">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-500/20 text-red-300 hover:bg-red-500/30 border border-red-500/30 text-xs font-semibold transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Dọn sạch Cache</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
