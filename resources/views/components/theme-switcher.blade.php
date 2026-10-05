@php
    $defaultMode = config('theme.mode', 'dark');
    $defaultAccent = config('theme.accent', 'indigo');
    $defaultGlow = config('theme.glow', true);
    $palettes = config('theme.palettes', []);
@endphp

<div class="relative inline-block text-left" 
     x-data="themeSwitcherApp({
         defaultMode: '{{ $defaultMode }}',
         defaultAccent: '{{ $defaultAccent }}',
         defaultGlow: {{ $defaultGlow ? 'true' : 'false' }},
         palettes: {{ json_encode($palettes) }}
     })" 
     @click.outside="open = false" 
     x-cloak>

    {{-- Trigger Button --}}
    <button @click="open = !open" 
            type="button" 
            class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full border text-xs font-medium transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 cursor-pointer"
            :class="isLight ? 'bg-white hover:bg-slate-100 border-slate-300 text-slate-800' : 'bg-slate-800/90 hover:bg-slate-700/90 border-slate-700 hover:border-slate-500 text-gray-200'"
            :title="'Giao diện: ' + currentModeLabel + ' | Màu: ' + currentPaletteName">
        
        {{-- Mode Icon --}}
        <span class="flex items-center justify-center w-4 h-4 flex-shrink-0">
            <template x-if="mode === 'dark'">
                <svg class="w-3.5 h-3.5 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                </svg>
            </template>
            <template x-if="mode === 'light'">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </template>
            <template x-if="mode === 'auto'">
                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </template>
        </span>

        {{-- Accent Color Glowing Dot --}}
        <span class="w-2.5 h-2.5 rounded-full shadow-sm ring-1 ring-white/30 transition-transform group-hover:scale-110 flex-shrink-0"
              :style="'background-color: ' + currentPaletteColor + '; box-shadow: 0 0 8px ' + (glow ? currentPaletteColor : 'transparent')"></span>

        {{-- Dropdown Chevron --}}
        <svg class="w-3 h-3 transition-transform duration-200 flex-shrink-0" 
             :class="[open ? 'rotate-180' : '', isLight ? 'text-slate-600' : 'text-gray-400']" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown Panel: 100% Solid Opaque Background (Never see-through, centered on mobile, right-aligned on desktop) --}}
    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
         :style="'background-color: ' + (isLight ? '#ffffff' : '#0c1222') + ' !important;'"
         class="fixed left-3 right-3 top-14 sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[340px] max-w-sm mx-auto rounded-2xl border shadow-[0_25px_60px_rgba(0,0,0,0.95)] p-4 z-[999] select-none"
         :class="isLight ? 'border-slate-200 text-slate-800 shadow-xl' : 'border-slate-700/80 text-gray-200'">
        
        {{-- Header --}}
        <div class="flex items-center justify-between pb-3 border-b"
             :class="isLight ? 'border-slate-200' : 'border-slate-700/70'">
            <div class="flex items-center gap-2">
                <span class="text-base">🎨</span>
                <span class="text-xs font-black tracking-wide uppercase"
                      :class="isLight ? 'text-slate-900' : 'text-white'">Tùy biến Giao diện</span>
            </div>
            <button type="button"
                    @click="resetToDefault()" 
                    class="text-[11px] font-semibold underline transition-colors cursor-pointer"
                    :class="isLight ? 'text-indigo-600 hover:text-indigo-800' : 'text-slate-400 hover:text-indigo-400'"
                    title="Khôi phục về cài đặt mặc định của hệ thống">
                Mặc định
            </button>
        </div>

        {{-- Section 1: Mode Switcher (Dark / Light / Auto) --}}
        <div class="py-3 border-b"
             :class="isLight ? 'border-slate-200' : 'border-slate-700/60'">
            <div class="text-[11px] font-semibold mb-2 flex items-center justify-between"
                 :class="isLight ? 'text-slate-600' : 'text-slate-400'">
                <span>Chế độ hiển thị</span>
                <span class="text-[10px] font-mono font-bold text-indigo-500" x-text="currentModeLabel"></span>
            </div>
            <div class="grid grid-cols-3 gap-1.5 p-1 rounded-xl border"
                 :class="isLight ? 'bg-slate-100 border-slate-200' : 'bg-slate-950/80 border-slate-800'">
                {{-- Dark Mode --}}
                <button type="button" 
                        @click="setMode('dark')"
                        class="flex flex-col items-center justify-center py-2 px-1 rounded-lg text-xs font-medium transition-all cursor-pointer"
                        :style="mode === 'dark' ? 'background: ' + currentPaletteGradient + ';' : ''"
                        :class="mode === 'dark' ? 'text-white shadow-md font-bold' : (isLight ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' : 'text-slate-400 hover:text-white hover:bg-slate-800/60')">
                    <svg class="w-4 h-4 mb-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                    <span>Tối</span>
                </button>

                {{-- Light Mode --}}
                <button type="button" 
                        @click="setMode('light')"
                        class="flex flex-col items-center justify-center py-2 px-1 rounded-lg text-xs font-medium transition-all cursor-pointer"
                        :style="mode === 'light' ? 'background: ' + currentPaletteGradient + ';' : ''"
                        :class="mode === 'light' ? 'text-white shadow-md font-bold' : (isLight ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' : 'text-slate-400 hover:text-white hover:bg-slate-800/60')">
                    <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Sáng</span>
                </button>

                {{-- System Auto --}}
                <button type="button" 
                        @click="setMode('auto')"
                        class="flex flex-col items-center justify-center py-2 px-1 rounded-lg text-xs font-medium transition-all cursor-pointer"
                        :style="mode === 'auto' ? 'background: ' + currentPaletteGradient + ';' : ''"
                        :class="mode === 'auto' ? 'text-white shadow-md font-bold' : (isLight ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' : 'text-slate-400 hover:text-white hover:bg-slate-800/60')">
                    <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Hệ thống</span>
                </button>
            </div>
        </div>

        {{-- Section 2: Accent Color Palette (7 Palettes) --}}
        <div class="py-3 border-b"
             :class="isLight ? 'border-slate-200' : 'border-slate-700/60'">
            <div class="text-[11px] font-semibold mb-2 flex items-center justify-between"
                 :class="isLight ? 'text-slate-600' : 'text-slate-400'">
                <span>Màu chủ đạo (Accent Palette)</span>
                <span class="text-[10px] font-mono font-bold" :style="'color: ' + currentPaletteColor" x-text="currentPaletteName"></span>
            </div>
            <div class="grid grid-cols-7 gap-2">
                <template x-for="(pal, key) in palettes" :key="pal.key || key">
                    <button type="button" 
                            @click="setAccent(pal.key || key)"
                            :title="pal.name"
                            class="relative group aspect-square rounded-xl flex items-center justify-center transition-all duration-200 cursor-pointer"
                            :style="'background: ' + pal.gradient + ';'"
                            :class="accent === (pal.key || key) ? 'ring-2 ring-white ring-offset-2 ring-offset-slate-900 scale-110 shadow-lg' : 'opacity-75 hover:opacity-100 hover:scale-105'">
                        
                        {{-- Active Indicator Check --}}
                        <svg x-show="accent === (pal.key || key)" class="w-3.5 h-3.5 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        {{-- Section 3: Neon Glow Ambient Effect Toggle --}}
        <div class="py-3 border-b flex items-center justify-between"
             :class="isLight ? 'border-slate-200' : 'border-slate-700/60'">
            <div class="flex items-center gap-2.5">
                <span class="text-sm">✨</span>
                <div>
                    <p class="text-xs font-bold leading-tight"
                       :class="isLight ? 'text-slate-900' : 'text-white'">Hiệu ứng Neon Glow</p>
                    <p class="text-[10px] leading-tight"
                       :class="isLight ? 'text-slate-500' : 'text-slate-400'">Ánh sáng viền nút bấm & khối giao diện</p>
                </div>
            </div>
            <button type="button" 
                    @click="toggleGlow()" 
                    class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :style="glow ? 'background: ' + currentPaletteGradient : ''"
                    :class="!glow ? (isLight ? 'bg-slate-300' : 'bg-slate-700') : ''">
                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="glow ? 'translate-x-5' : 'translate-x-0'"></span>
            </button>
        </div>

        {{-- Section 4: Live Preview Swatch & Apply Button --}}
        <div class="pt-3">
            <div class="p-2.5 rounded-xl border transition-all duration-300 flex items-center justify-between text-xs"
                 :class="isLight ? 'bg-slate-100 border-slate-200 text-slate-800' : 'bg-slate-950 border-slate-800 text-white'">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full flex-shrink-0 shadow-sm" :style="'background-color: ' + currentPaletteColor"></span>
                    <span class="font-medium text-[11px] truncate" x-text="'Xem trước: ' + currentPaletteName"></span>
                </div>
                <button type="button" 
                        @click="applyTheme(); open = false"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white transition-all shadow-md hover:scale-105 active:scale-95 flex-shrink-0 cursor-pointer"
                        :style="'background: ' + currentPaletteGradient + '; box-shadow: 0 0 12px ' + (glow ? currentPaletteGlow : 'transparent')">
                    Áp dụng
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function themeSwitcherApp(config) {
        return {
            open: false,
            mode: 'dark',
            accent: 'indigo',
            glow: true,
            palettes: config.palettes && Object.keys(config.palettes).length > 0 ? config.palettes : {
                blue: { key: 'blue', name: 'Ocean Blue', primary: '#3b82f6', hover: '#2563eb', gradient: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)', glow: 'rgba(59, 130, 246, 0.35)', badgeBg: 'rgba(59, 130, 246, 0.15)', badgeText: '#93c5fd' },
                indigo: { key: 'indigo', name: 'Electric Indigo', primary: '#6366f1', hover: '#4f46e5', gradient: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)', glow: 'rgba(99, 102, 241, 0.35)', badgeBg: 'rgba(99, 102, 241, 0.15)', badgeText: '#a5b4fc' },
                purple: { key: 'purple', name: 'Royal Violet', primary: '#8b5cf6', hover: '#7c3aed', gradient: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)', glow: 'rgba(139, 92, 246, 0.35)', badgeBg: 'rgba(139, 92, 246, 0.15)', badgeText: '#c4b5fd' },
                emerald: { key: 'emerald', name: 'Emerald Mint', primary: '#10b981', hover: '#059669', gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)', glow: 'rgba(16, 185, 129, 0.35)', badgeBg: 'rgba(16, 185, 129, 0.15)', badgeText: '#6ee7b7' },
                amber: { key: 'amber', name: 'Sunset Amber', primary: '#f59e0b', hover: '#d97706', gradient: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', glow: 'rgba(245, 158, 11, 0.35)', badgeBg: 'rgba(245, 158, 11, 0.15)', badgeText: '#fcd34d' },
                rose: { key: 'rose', name: 'Crimson Rose', primary: '#f43f5e', hover: '#e11d48', gradient: 'linear-gradient(135deg, #f43f5e 0%, #be123c 100%)', glow: 'rgba(244, 63, 94, 0.35)', badgeBg: 'rgba(244, 63, 94, 0.15)', badgeText: '#fda4af' },
                cyan: { key: 'cyan', name: 'Cyber Cyan', primary: '#06b6d4', hover: '#0891b2', gradient: 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)', glow: 'rgba(6, 182, 212, 0.35)', badgeBg: 'rgba(6, 182, 212, 0.15)', badgeText: '#67e8f9' }
            },
            
            init() {
                // Read from localStorage or fallback to system defaults
                const savedMode = localStorage.getItem('esl_theme_mode') || config.defaultMode || 'dark';
                const savedAccent = localStorage.getItem('esl_theme_accent') || config.defaultAccent || 'indigo';
                const savedGlow = localStorage.getItem('esl_theme_glow') !== null 
                    ? localStorage.getItem('esl_theme_glow') === 'true' 
                    : (config.defaultGlow !== false);

                this.mode = savedMode;
                this.accent = savedAccent;
                this.glow = savedGlow;

                this.applyTheme();

                // Listen to external changes or system preference changes
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (this.mode === 'auto') {
                        this.applyTheme();
                    }
                });
            },

            get isLight() {
                if (this.mode === 'light') return true;
                if (this.mode === 'dark') return false;
                return !window.matchMedia('(prefers-color-scheme: dark)').matches;
            },

            get currentPaletteColor() {
                return this.palettes[this.accent]?.primary || '#6366f1';
            },

            get currentPaletteGradient() {
                return this.palettes[this.accent]?.gradient || 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)';
            },

            get currentPaletteGlow() {
                return this.palettes[this.accent]?.glow || 'rgba(99, 102, 241, 0.35)';
            },

            get currentPaletteName() {
                return this.palettes[this.accent]?.name || 'Electric Indigo';
            },

            get currentModeLabel() {
                if (this.mode === 'dark') return 'Giao diện Tối';
                if (this.mode === 'light') return 'Giao diện Sáng';
                return 'Hệ thống (Auto)';
            },

            setMode(newMode) {
                this.mode = newMode;
                localStorage.setItem('esl_theme_mode', newMode);
                this.applyTheme();
            },

            setAccent(newAccent) {
                this.accent = newAccent;
                localStorage.setItem('esl_theme_accent', newAccent);
                this.applyTheme();
            },

            toggleGlow() {
                this.glow = !this.glow;
                localStorage.setItem('esl_theme_glow', this.glow);
                this.applyTheme();
            },

            resetToDefault() {
                this.mode = config.defaultMode || 'dark';
                this.accent = config.defaultAccent || 'indigo';
                this.glow = config.defaultGlow !== false;

                localStorage.removeItem('esl_theme_mode');
                localStorage.removeItem('esl_theme_accent');
                localStorage.removeItem('esl_theme_glow');

                this.applyTheme();
            },

            applyTheme() {
                const root = document.documentElement;
                const isDark = !this.isLight;

                if (isDark) {
                    root.classList.add('dark');
                    root.classList.remove('light');
                } else {
                    root.classList.remove('dark');
                    root.classList.add('light');
                }

                root.setAttribute('data-accent', this.accent);

                if (this.glow) {
                    root.setAttribute('data-glow', 'true');
                } else {
                    root.setAttribute('data-glow', 'false');
                }

                // Directly apply CSS variables to root style for immediate reactive update
                const pal = this.palettes[this.accent] || {
                    primary: '#6366f1',
                    hover: '#4f46e5',
                    gradient: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
                    glow: 'rgba(99, 102, 241, 0.35)',
                    badgeBg: 'rgba(99, 102, 241, 0.15)',
                    badgeText: '#a5b4fc'
                };
                root.style.setProperty('--theme-primary', pal.primary);
                root.style.setProperty('--theme-primary-hover', pal.hover || pal.primary);
                root.style.setProperty('--theme-gradient', pal.gradient);
                root.style.setProperty('--theme-primary-glow', pal.glow);
                root.style.setProperty('--theme-border-glow', (pal.glow || '').replace('0.35', '0.5') || 'rgba(99, 102, 241, 0.5)');
                root.style.setProperty('--theme-badge-bg', pal.badgeBg || (pal.glow || '').replace('0.35', '0.15') || 'rgba(99, 102, 241, 0.15)');
                root.style.setProperty('--theme-badge-text', pal.badgeText || '#a5b4fc');

                // Dispatch event so other components (e.g. chart/audio visualizer) can adapt if needed
                window.dispatchEvent(new CustomEvent('theme-changed', {
                    detail: {
                        mode: this.mode,
                        isDark: isDark,
                        accent: this.accent,
                        glow: this.glow,
                        palette: pal
                    }
                }));
            }
        };
    }
</script>
