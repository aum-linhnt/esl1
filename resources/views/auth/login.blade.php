<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập - ESL LMS</title>
    <meta name="description" content="Đăng nhập vào hệ thống ESL - Trung tâm ngoại ngữ trong túi của bạn">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-fsel-dark min-h-screen" x-data="{ chatOpen: false }">
    <div class="min-h-screen flex flex-col lg:flex-row">
        {{-- ============ LEFT COLUMN - Login Form (Compact & Focused) ============ --}}
        <div class="w-full lg:w-[460px] xl:w-[500px] flex-shrink-0 flex flex-col min-h-screen relative bg-fsel-darker z-10 border-r border-fsel-border/20 shadow-2xl">
            
            {{-- Top Banner --}}
            <div class="w-full bg-fsel-dark/80 border-b border-fsel-border/30 px-4 py-2.5 text-center">
                <p class="text-xs text-gray-300">
                    <span class="text-fsel-teal mr-1">⊕</span>
                    LƯU Ý: Giáo viên tham gia Tháng tự học tiếng Anh toàn quốc
                    vui lòng đăng ký tại: 
                    <a href="https://thangtuhoctoanquoc.fsel.vn" class="text-fsel-teal font-semibold hover:underline" target="_blank">
                        thangtuhoctoanquoc.fsel.vn
                    </a>
                </p>
            </div>

            {{-- Form Content --}}
            <div class="flex-1 flex flex-col items-center justify-center px-6 sm:px-12 lg:px-16 py-8">
                {{-- Logo --}}
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 relative">
                        <svg viewBox="0 0 48 48" class="w-full h-full">
                            <circle cx="24" cy="24" r="22" fill="none" stroke="#6366f1" stroke-width="1.5" opacity="0.6"/>
                            <circle cx="24" cy="24" r="16" fill="none" stroke="#818cf8" stroke-width="1" opacity="0.4"/>
                            <circle cx="24" cy="24" r="4" fill="#a5b4fc"/>
                            <circle cx="24" cy="8" r="2" fill="#fbbf24"/>
                            <path d="M24 8 Q30 16 24 24" fill="none" stroke="#fbbf24" stroke-width="1" opacity="0.6"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white tracking-wide">
                            E<span class="text-fsel-accent">S</span><span class="text-fsel-teal">L</span>
                        </h1>
                        <p class="text-[10px] text-gray-400 tracking-[0.2em] -mt-0.5 italic">Reach for the stars</p>
                    </div>
                </div>

                {{-- Title --}}
                <h2 class="text-xl font-bold text-white mb-6 tracking-wide">ĐĂNG NHẬP</h2>

                {{-- Info alert --}}
                @if(session('info'))
                    <div class="w-full max-w-sm mb-4 bg-fsel-blue/20 border border-fsel-blue/40 text-fsel-blue px-3.5 py-2 rounded-lg text-xs text-center">
                        {{ session('info') }}
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm space-y-4">
                    @csrf

                    {{-- Username / Email --}}
                    <div>
                        <label for="login" class="login-label">Tên đăng nhập / Email</label>
                        <input 
                            id="login" 
                            type="text" 
                            name="login"
                            value="{{ old('login') }}"
                            placeholder="tuanlinh, admin, hoặc giaovien1"
                            class="login-input" 
                            required 
                            autofocus
                        >
                        @error('login')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="login-label">Mật khẩu</label>
                        <div class="relative">
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                placeholder="••••••••"
                                class="login-input pr-12" 
                                required
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()" 
                                id="toggle-password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
                            >
                                <svg id="eye-off" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                </svg>
                                <svg id="eye-on" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember & Forgot --}}
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                id="remember"
                                class="w-4 h-4 rounded border-fsel-border bg-fsel-input text-fsel-accent focus:ring-fsel-accent focus:ring-offset-0"
                            >
                            <span class="text-sm text-gray-300">Duy trì đăng nhập</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm text-gray-400 hover:text-fsel-teal transition-colors">
                            Quên mật khẩu?
                        </a>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn-primary mt-2">
                        Đăng nhập
                    </button>
                </form>

                {{-- Divider --}}
                <div class="flex items-center gap-4 my-6 w-full max-w-sm">
                    <div class="flex-1 h-px bg-fsel-border"></div>
                    <span class="text-sm text-gray-400">Hoặc</span>
                    <div class="flex-1 h-px bg-fsel-border"></div>
                </div>

                {{-- Social Login --}}
                <div class="flex items-center gap-4">
                    {{-- Google --}}
                    <a href="{{ route('social.redirect', 'google') }}" class="social-btn" title="Đăng nhập với Google">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                    </a>
                    {{-- Apple --}}
                    <a href="{{ route('social.redirect', 'apple') }}" class="social-btn" title="Đăng nhập với Apple">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                        </svg>
                    </a>
                    {{-- Zalo --}}
                    <a href="{{ route('social.redirect', 'zalo') }}" class="social-btn" title="Đăng nhập với Zalo">
                        <span class="text-sm font-bold text-blue-400">Zalo</span>
                    </a>
                    {{-- vnEdu --}}
                    <a href="{{ route('social.redirect', 'vnedu') }}" class="social-btn" title="Đăng nhập với vnEdu">
                        <span class="text-xs font-bold">
                            <span class="text-red-400">vn</span><span class="text-blue-400">Edu</span>
                        </span>
                    </a>
                </div>

                {{-- Demo credentials hint --}}
                <div class="mt-8 text-center text-xs text-gray-500 bg-fsel-navy/40 border border-fsel-border/30 rounded-lg p-3 w-full max-w-sm">
                    <p class="font-semibold text-gray-400 mb-1">🔑 Tài khoản thử nghiệm:</p>
                    <p>Học viên: <code class="text-fsel-teal">tuanlinh</code> / <code class="text-fsel-teal">password123</code></p>
                    <p>Giáo viên: <code class="text-fsel-teal">giaovien1</code> / <code class="text-fsel-teal">password123</code></p>
                    <p>Admin: <code class="text-fsel-teal">admin</code> / <code class="text-fsel-teal">password123</code></p>
                </div>
            </div>
        </div>

        {{-- ============ RIGHT COLUMN - Illustration (Expansive Space Theme) ============ --}}
        <div class="hidden lg:flex flex-1 relative overflow-hidden bg-gradient-purple items-center justify-center min-h-screen">
            {{-- Stars --}}
            <div class="absolute inset-0">
                @for ($i = 0; $i < 30; $i++)
                    <div class="star" style="left: {{ rand(5, 95) }}%; top: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.2 }}s; opacity: {{ rand(2, 8) / 10 }};"></div>
                @endfor
            </div>

            {{-- Planets / Moons --}}
            <div class="absolute top-10 right-16 w-24 h-24 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 opacity-80 shadow-lg"></div>
            <div class="absolute top-6 right-12 w-8 h-8 rounded-full bg-gray-400 opacity-50"></div>

            {{-- Mountains --}}
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 800 200" class="w-full" preserveAspectRatio="none">
                    <path d="M0 200 L0 140 Q100 80 200 120 Q300 60 400 100 Q500 40 600 90 Q700 60 800 100 L800 200 Z" fill="#2d1b69" opacity="0.6"/>
                    <path d="M0 200 L0 160 Q150 120 300 150 Q450 100 600 140 Q700 110 800 140 L800 200 Z" fill="#1a1a4e" opacity="0.8"/>
                    <path d="M0 200 L0 175 Q200 150 400 170 Q600 145 800 165 L800 200 Z" fill="#12123a"/>
                </svg>
            </div>

            {{-- Rocket --}}
            <div class="absolute bottom-24 left-1/4 animate-rocket">
                <svg width="80" height="160" viewBox="0 0 80 160" class="drop-shadow-2xl">
                    <defs>
                        <linearGradient id="rocketGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#8b5cf6"/>
                            <stop offset="50%" stop-color="#6d28d9"/>
                            <stop offset="100%" stop-color="#4c1d95"/>
                        </linearGradient>
                        <linearGradient id="flameGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#f59e0b"/>
                            <stop offset="50%" stop-color="#ef4444"/>
                            <stop offset="100%" stop-color="#ef444400"/>
                        </linearGradient>
                    </defs>
                    <path d="M40 10 L55 50 L25 50 Z" fill="#c4b5fd"/>
                    <rect x="25" y="50" width="30" height="60" rx="3" fill="url(#rocketGrad)"/>
                    <circle cx="40" cy="72" r="10" fill="#1e1b4b" stroke="#a5b4fc" stroke-width="2"/>
                    <circle cx="40" cy="72" r="6" fill="#312e81" opacity="0.8"/>
                    <circle cx="37" cy="69" r="2" fill="#c4b5fd" opacity="0.6"/>
                    <path d="M25 95 L10 120 L25 110 Z" fill="#7c3aed"/>
                    <path d="M55 95 L70 120 L55 110 Z" fill="#7c3aed"/>
                    <ellipse cx="40" cy="120" rx="10" ry="25" fill="url(#flameGrad)" opacity="0.9">
                        <animate attributeName="ry" values="25;30;20;25" dur="0.5s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" values="0.9;0.7;0.9" dur="0.3s" repeatCount="indefinite"/>
                    </ellipse>
                    <ellipse cx="40" cy="118" rx="5" ry="15" fill="#fbbf24" opacity="0.8">
                        <animate attributeName="ry" values="15;18;12;15" dur="0.4s" repeatCount="indefinite"/>
                    </ellipse>
                </svg>
            </div>

            {{-- Text Content --}}
            <div class="relative z-10 text-center px-8 max-w-lg">
                <h2 class="text-4xl lg:text-5xl font-bold text-white leading-tight mb-8 drop-shadow-lg">
                    Trung tâm ngoại ngữ trong túi của bạn
                </h2>
                <a href="{{ route('login') }}" class="btn-outline inline-block text-base">
                    Đánh giá năng lực ngoại ngữ hoàn toàn miễn phí
                </a>
            </div>

            {{-- Floating small planets --}}
            <div class="absolute top-1/4 left-8 w-4 h-4 rounded-full bg-purple-400 opacity-50 animate-float" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/3 right-20 w-3 h-3 rounded-full bg-indigo-300 opacity-40 animate-float" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-1/3 right-8 w-5 h-5 rounded-full bg-violet-400 opacity-30 animate-float" style="animation-delay: 0.5s;"></div>
        </div>
    </div>

    {{-- Floating Chat Button + Popup on Login page --}}
    <div class="fixed bottom-6 right-6 z-50">
        <div x-show="chatOpen" x-transition @click.outside="chatOpen = false" class="mb-4 w-80 bg-fsel-card border border-fsel-border rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-fsel-blue to-fsel-purple px-4 py-3 flex items-center justify-between">
                <span class="text-white font-semibold text-sm">ESL Support</span>
                <button @click="chatOpen = false" class="text-white/70 hover:text-white">&times;</button>
            </div>
            <div class="p-4 text-sm text-gray-300">
                <p>👋 Xin chào! Hãy đăng nhập để chat với trợ lý học tập ESL nhé.</p>
            </div>
        </div>
        <button @click="chatOpen = !chatOpen" class="chat-btn">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
            </svg>
            <span class="text-sm font-medium">Chat</span>
        </button>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeOff = document.getElementById('eye-off');
            const eyeOn = document.getElementById('eye-on');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeOff.classList.add('hidden');
                eyeOn.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOff.classList.remove('hidden');
                eyeOn.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
