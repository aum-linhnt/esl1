<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - ESL LMS</title>
    <meta name="description" content="ESL LMS - Hệ thống quản lý học tập tiếng Anh thông minh">
    <style>
        [x-cloak] { display: none !important; }
        .card-dark {
            background: rgba(15, 23, 42, 0.78) !important;
            border: 1px solid rgba(51, 65, 85, 0.6) !important;
            border-radius: 1rem !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.25) !important;
        }
        .bg-clip-text {
            -webkit-background-clip: text !important;
            background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-fsel-dark text-white min-h-screen antialiased selection:bg-indigo-500 selection:text-white overflow-x-hidden max-w-full" 
      x-data="{ sidebarOpen: window.innerWidth >= 1024, chatOpen: false, showChatBubble: true }"
      @resize.window="if (window.innerWidth < 1024 && sidebarOpen) { sidebarOpen = false; }">
    <div class="flex min-h-screen w-full max-w-full overflow-x-hidden">
        @include('components.sidebar')

        <div class="flex-1 min-w-0 flex flex-col min-h-screen w-full max-w-full overflow-x-hidden transition-all duration-300"
             :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'">
            @include('components.header')

            <main class="flex-1 min-w-0 px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6 pb-24 lg:pb-8 w-full max-w-full">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-4 bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-lg text-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg text-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-4 bg-blue-500/20 border border-blue-500/50 text-blue-300 px-4 py-3 rounded-lg text-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                        {{ session('info') }}
                    </div>
                @endif

                @yield('content')
            </main>

            @include('components.footer')
        </div>
    </div>

    {{-- Mobile Bottom App Navigation --}}
    @include('components.bottom-nav')

    {{-- Floating Chat Assistant --}}
    <div class="fixed floating-ai-btn right-3 sm:right-6 z-50 flex flex-col items-end">
        {{-- Chat Popup --}}
        <div x-show="chatOpen" 
             x-cloak
             style="display: none;"
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-4" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
             x-transition:leave-end="opacity-0 scale-95 translate-y-4" 
             @click.outside="chatOpen = false" 
             class="w-[calc(100vw-2rem)] max-w-sm sm:w-96 bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl overflow-hidden backdrop-blur-xl">
            
            {{-- Chat Header --}}
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-teal-500 px-4 py-3.5 flex items-center justify-between shadow-md">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm shadow-inner">
                        🤖
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-xs">Trợ lý học tập ESL AI</h4>
                        <span class="text-[10px] text-teal-200 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Đang trực tuyến
                        </span>
                    </div>
                </div>
                <button @click="chatOpen = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors" title="Đóng cửa sổ chat">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Chat Messages --}}
            <div class="p-4 h-72 overflow-y-auto space-y-3 bg-slate-950/60" id="chat-messages">
                <div class="flex gap-2">
                    <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex-shrink-0 flex items-center justify-center text-xs">
                        🤖
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl rounded-tl-sm px-3.5 py-2.5 max-w-[85%]">
                        <p class="text-xs text-gray-200 leading-relaxed">Xin chào! 👋 Tôi là trợ lý ảo ESL. Bạn cần hỗ trợ từ vựng, ngữ pháp hay hướng dẫn bài học nào không?</p>
                        <span class="text-[9px] text-gray-500 mt-1 block">Vừa xong</span>
                    </div>
                </div>
            </div>

            {{-- Chat Input & Quota --}}
            @php
                $chatUser = auth()->user();
                $chatRemaining = \App\Http\Controllers\AiChatController::getRemainingQuestions($chatUser?->id, request()->ip());
            @endphp
            <div class="border-t border-slate-800/80 p-3 bg-slate-900/95">
                {{-- Daily Quota Counter --}}
                <div class="text-[12px] text-slate-400 font-normal px-0.5 pb-2">
                    Lượt hỏi hôm nay: <span id="chat-quota-text" class="{{ $chatRemaining > 0 ? 'text-slate-300 font-semibold' : 'text-rose-400 font-semibold' }}">{{ $chatRemaining }}/10</span>
                </div>

                <form onsubmit="sendChatMessage(event)" class="flex items-center gap-2">
                    <input type="text" id="chat-input" 
                           {{ $chatRemaining <= 0 ? 'disabled' : '' }}
                           class="flex-1 bg-slate-950/90 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white placeholder-gray-400 focus:outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed" 
                           placeholder="{{ $chatRemaining <= 0 ? 'Hết lượt hỏi hôm nay (0/10)...' : 'Soạn tin nhắn...' }}">
                    <button type="submit" id="chat-submit-btn" 
                            {{ $chatRemaining <= 0 ? 'disabled' : '' }}
                            class="bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/70 text-slate-400 hover:text-sky-400 rounded-xl p-2.5 transition-all shadow-md hover:scale-105 active:scale-95 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            title="Gửi câu hỏi">
                        <svg class="w-4 h-4 stroke-current" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3 21l18-9L3 3l3 9zm0 0h9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Floating Chat Trigger (Compact Mascot Button + Desktop Speech Bubble) --}}
        <div x-show="!chatOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-75"
             x-transition:enter-end="opacity-100 scale-100"
             class="flex items-center gap-2.5 relative">

            {{-- Speech Bubble: Hidden on mobile (< sm), visible on desktop --}}
            <div x-show="showChatBubble"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="hidden sm:flex items-center relative bg-[#172338] text-slate-100 text-xs sm:text-sm font-medium px-4 py-2.5 rounded-2xl shadow-2xl border border-slate-700/80 select-none whitespace-nowrap cursor-pointer hover:border-sky-500/40 transition-colors"
                 @click="chatOpen = true">
                
                {{-- Dismiss Button (x) at top-left --}}
                <button @click.stop="showChatBubble = false" 
                        class="absolute -top-2 -left-2 w-4 h-4 rounded bg-[#22324e] border border-slate-600 text-slate-400 hover:text-white flex items-center justify-center text-[11px] leading-none shadow-md hover:scale-110 transition-transform cursor-pointer"
                        title="Đóng gợi ý">
                    &times;
                </button>

                <span>Bạn có cần hỗ trợ gì không?</span>
            </div>

            {{-- Squircle Robot Mascot Button (Mobile: Only Icon) --}}
            <button @click="chatOpen = true" 
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#172338] hover:bg-[#1e2f4a] border border-slate-700/90 hover:border-sky-400/60 shadow-xl shadow-black/50 hover:scale-105 active:scale-95 transition-all flex items-center justify-center p-2 sm:p-2.5 cursor-pointer backdrop-blur-md group flex-shrink-0"
                    title="Trợ lý học tập ESL AI">
                <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow-md transition-transform group-hover:scale-110" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Left Antenna -->
                    <path d="M 37 34 L 28 17" stroke="#0072ff" stroke-width="7" stroke-linecap="round"/>
                    <!-- Right Antenna -->
                    <path d="M 67 30 L 79 15" stroke="#0072ff" stroke-width="7" stroke-linecap="round"/>
                    
                    <!-- Outer Blue Head with Chat Bubble Tail -->
                    <path d="M 50 25 C 31 25 19 37 19 51 C 19 63 27 72 39 76 C 43 77 47 78 50 78 C 55 78 59 77 63 75 L 68 87 C 68.8 88.8 71.2 88.2 71.5 86.3 L 73 72.5 C 79 67.5 82 59.5 82 51 C 82 37 69 25 50 25 Z" fill="#0072ff"/>
                    
                    <!-- Left Ear headphone (white pill) -->
                    <ellipse cx="20" cy="51" rx="4.5" ry="9.5" fill="#ffffff"/>
                    
                    <!-- Right Ear headphone (white pill) -->
                    <ellipse cx="80" cy="51" rx="4.5" ry="9.5" fill="#ffffff"/>
                    
                    <!-- Inner White Chat Bubble Face -->
                    <path d="M 50 36 C 38 36 30 43.5 30 52 C 30 58.5 35 64 43.5 66 C 45.5 66.5 48 67 50 67 C 53 67 56 66.2 59 65.2 L 62.5 72.5 C 63 73.5 64.2 73.2 64.5 72.2 L 65 63.2 C 68.5 60.5 70 56.5 70 52 C 70 43.5 62 36 50 36 Z" fill="#ffffff"/>
                    
                    <!-- Left Eye (Curved Blue) -->
                    <path d="M 42 47 C 44.5 47 46 49.5 46 52.5 C 46 55.5 44.5 57.5 42 57.5 C 40 57.5 40.5 55.5 40.5 52.5 C 40.5 49.5 40 47 42 47 Z" fill="#0072ff"/>
                    <!-- Right Eye (Curved Blue) -->
                    <path d="M 55 47 C 57.5 47 59 49.5 59 52.5 C 59 55.5 57.5 57.5 55 57.5 C 53 57.5 53.5 55.5 53.5 52.5 C 53.5 49.5 53 47 55 47 Z" fill="#0072ff"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
        function sendChatMessage(e) {
            e.preventDefault();
            const input = document.getElementById('chat-input');
            const messages = document.getElementById('chat-messages');
            const text = input.value.trim();
            if (!text) return;

            // Check remaining quota client-side
            const quotaText = document.getElementById('chat-quota-text');
            if (quotaText && quotaText.textContent.trim() === '0/10') {
                alert('Bạn đã sử dụng hết 10/10 lượt hỏi AI hôm nay. Hãy quay lại vào ngày mai nhé!');
                return;
            }

            // Render User message
            messages.innerHTML += `
                <div class="flex gap-2 justify-end">
                    <div class="bg-fsel-blue/20 border border-fsel-blue/30 rounded-xl rounded-tr-sm px-3 py-2 max-w-[85%]">
                        <p class="text-xs text-white leading-relaxed">${text}</p>
                        <span class="text-[9px] text-gray-400 mt-1 block text-right">Vừa xong</span>
                    </div>
                </div>`;

            input.value = '';
            messages.scrollTop = messages.scrollHeight;

            // Typing indicator
            const typingId = 'typing-' + Date.now();
            messages.innerHTML += `
                <div id="${typingId}" class="flex gap-2 items-center">
                    <div class="w-6 h-6 rounded-full bg-fsel-blue/30 flex-shrink-0 flex items-center justify-center text-xs">🤖</div>
                    <div class="bg-fsel-navy rounded-xl px-3 py-2 text-xs text-gray-400 flex items-center gap-1">
                        <span>ESL Bot đang soạn trả lời</span>
                        <span class="animate-pulse">...</span>
                    </div>
                </div>`;
            messages.scrollTop = messages.scrollHeight;

            fetch('{{ route("api.ai.chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message: text })
            })
            .then(r => r.json())
            .then(data => {
                const typingEl = document.getElementById(typingId);
                if (typingEl) typingEl.remove();

                // Update quota display dynamically
                if (typeof data.remaining !== 'undefined') {
                    const qText = document.getElementById('chat-quota-text');
                    const cInput = document.getElementById('chat-input');
                    const sBtn = document.getElementById('chat-submit-btn');

                    if (qText) {
                        qText.textContent = data.remaining + '/10';
                        if (data.remaining <= 0) {
                            qText.className = 'text-rose-400 font-semibold';
                            if (cInput) {
                                cInput.disabled = true;
                                cInput.placeholder = 'Hết lượt hỏi hôm nay (0/10)...';
                            }
                            if (sBtn) {
                                sBtn.disabled = true;
                            }
                        }
                    }
                }

                const reply = data.reply || 'Tôi có thể hỗ trợ gì cho việc học tiếng Anh của bạn?';
                messages.innerHTML += `
                    <div class="flex gap-2">
                        <div class="w-7 h-7 rounded-full bg-fsel-blue/30 flex-shrink-0 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-fsel-blue" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        </div>
                        <div class="bg-fsel-navy rounded-xl rounded-tl-sm px-3 py-2 max-w-[85%] border border-fsel-border/30">
                            <p class="text-xs text-gray-200 leading-relaxed">${reply.replace(/\n/g, '<br>')}</p>
                            <span class="text-[9px] text-fsel-teal mt-1 block">${data.timestamp || 'Vừa xong'}</span>
                        </div>
                    </div>`;
                messages.scrollTop = messages.scrollHeight;
            })
            .catch(() => {
                const typingEl = document.getElementById(typingId);
                if (typingEl) typingEl.remove();
            });
        }
    </script>
</body>
</html>
