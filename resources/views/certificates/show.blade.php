<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chứng nhận hoàn thành khóa học - {{ $certificate->certificate_code }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            body { background: #0f172a !important; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-fsel-dark min-h-screen text-white flex flex-col items-center justify-center p-4 sm:p-8">

    {{-- Top Action Toolbar --}}
    <div class="no-print max-w-4xl w-full flex items-center justify-between mb-6">
        <a href="{{ route('courses.show', $course->id) }}" class="text-sm text-gray-400 hover:text-white flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Quay lại khóa học
        </a>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-primary !w-auto !py-2 px-6 text-sm flex items-center gap-2 shadow-glow-blue">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>In chứng chỉ / Xuất PDF</span>
            </button>
        </div>
    </div>

    {{-- CERTIFICATE FRAME --}}
    <div class="max-w-4xl w-full bg-gradient-to-br from-slate-900 via-fsel-navy to-indigo-950 border-4 border-yellow-500/60 rounded-3xl p-8 sm:p-14 shadow-2xl relative overflow-hidden">
        
        {{-- Luxury Corner Ornaments --}}
        <div class="absolute top-4 left-4 w-12 h-12 border-t-2 border-l-2 border-yellow-400 opacity-60"></div>
        <div class="absolute top-4 right-4 w-12 h-12 border-t-2 border-r-2 border-yellow-400 opacity-60"></div>
        <div class="absolute bottom-4 left-4 w-12 h-12 border-b-2 border-l-2 border-yellow-400 opacity-60"></div>
        <div class="absolute bottom-4 right-4 w-12 h-12 border-b-2 border-r-2 border-yellow-400 opacity-60"></div>

        <div class="text-center space-y-6 relative z-10">
            
            {{-- Header Logo --}}
            <div class="flex items-center justify-center gap-2 mb-2">
                <div class="w-10 h-10">
                    <svg viewBox="0 0 48 48" class="w-full h-full">
                        <circle cx="24" cy="24" r="22" fill="none" stroke="#6366f1" stroke-width="1.5"/>
                        <circle cx="24" cy="24" r="16" fill="none" stroke="#818cf8" stroke-width="1"/>
                        <circle cx="24" cy="24" r="4" fill="#a5b4fc"/>
                        <circle cx="24" cy="8" r="2" fill="#fbbf24"/>
                    </svg>
                </div>
                <span class="text-2xl font-black text-white tracking-widest">E<span class="text-fsel-accent">S</span><span class="text-fsel-teal">L</span></span>
            </div>

            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-yellow-400 font-bold">ESL English Learning Academy</p>
                <h1 class="text-3xl sm:text-5xl font-serif font-bold text-white mt-2 tracking-wide">
                    CHỨNG NHẬN HOÀN THÀNH
                </h1>
                <p class="text-xs text-gray-400 italic mt-1">Certificate of Course Completion</p>
            </div>

            <div class="py-2">
                <p class="text-xs text-gray-400">Chứng nhận học viên:</p>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-yellow-100 to-yellow-400 mt-2 font-serif">
                    {{ $user->name }}
                </h2>
                <div class="w-32 h-0.5 bg-gradient-to-r from-transparent via-yellow-400 to-transparent mx-auto mt-2"></div>
            </div>

            <div class="max-w-xl mx-auto text-sm text-gray-300 leading-relaxed">
                Đã hoàn thành xuất sắc toàn bộ chương trình và các bài đánh giá năng lực của khóa học:
                <p class="text-lg font-bold text-white mt-2 text-fsel-teal">
                    {{ $course->title }} (CEFR {{ $certificate->level }})
                </p>
            </div>

            {{-- Footer Signatures & QR Code --}}
            <div class="pt-8 border-t border-yellow-500/20 grid grid-cols-3 items-center text-left text-xs">
                <div>
                    <span class="text-gray-400 block text-[10px]">Mã xác thực duy nhất:</span>
                    <span class="font-mono text-yellow-400 font-bold">{{ $certificate->certificate_code }}</span>
                    <span class="text-gray-500 block text-[10px] mt-1">Ngày cấp: {{ $certificate->issued_at->format('d/m/Y') }}</span>
                </div>

                {{-- Golden Seal Stamp --}}
                <div class="flex justify-center">
                    <div class="w-20 h-20 rounded-full border-2 border-yellow-400/80 bg-yellow-500/10 flex flex-col items-center justify-center p-2 text-center shadow-glow-gold animate-float" style="animation-duration: 4s;">
                        <span class="text-[8px] font-bold text-yellow-300 uppercase tracking-tighter">ESL VERIFIED</span>
                        <span class="text-lg">⭐</span>
                        <span class="text-[8px] text-yellow-400 font-mono">100% PASS</span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-gray-400 block text-[10px]">Đại diện học viện:</span>
                    <p class="font-serif italic text-white text-base mt-1">ESL Academic Board</p>
                    <span class="text-[10px] text-fsel-teal">Verified by AI Engine</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
