@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
    <h2 class="text-2xl font-bold text-white mb-6">Marketplace</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach([
            ['Gói Basic', '99.000đ/tháng', 'Truy cập 2 khóa học A1-A2, luyện đề không giới hạn, 50 coins/tháng', 'from-blue-500 to-cyan-500'],
            ['Gói Premium', '199.000đ/tháng', 'Truy cập tất cả khóa học, luyện đề + AI chấm, 200 coins/tháng, chat hỗ trợ 24/7', 'from-purple-500 to-pink-500'],
            ['Gói VIP', '399.000đ/tháng', 'Tất cả Premium + Giáo viên riêng, lộ trình cá nhân hóa, 500 coins/tháng', 'from-yellow-500 to-orange-500'],
        ] as $index => $pkg)
            <div class="card-dark p-6 relative overflow-hidden {{ $index === 1 ? 'border-fsel-accent/50 ring-1 ring-fsel-accent/30' : '' }}">
                @if($index === 1)
                    <span class="absolute top-3 right-3 bg-fsel-accent text-fsel-dark text-[10px] font-bold px-2 py-0.5 rounded">PHỔ BIẾN</span>
                @endif
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $pkg[3] }} flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-1">{{ $pkg[0] }}</h3>
                <p class="text-2xl font-bold text-fsel-accent mb-3">{{ $pkg[1] }}</p>
                <p class="text-sm text-gray-400 mb-6 leading-relaxed">{{ $pkg[2] }}</p>
                <button class="w-full py-2.5 rounded-lg text-sm font-semibold transition-all {{ $index === 1 ? 'bg-gradient-btn text-white hover:opacity-90' : 'bg-fsel-navy border border-fsel-border text-gray-300 hover:border-fsel-accent hover:text-white' }}">
                    Đăng ký ngay
                </button>
            </div>
        @endforeach
    </div>
</div>
@endsection
