@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto text-center py-8">
    <h2 class="text-2xl font-bold text-white mb-8">Gia hạn tài khoản ESL</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card-dark p-6 border-fsel-teal/30 hover:border-fsel-teal/60 transition-colors">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-fsel-teal/20 flex items-center justify-center">
                <svg class="w-7 h-7 text-fsel-teal" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Gia hạn 1 tháng</h3>
            <p class="text-3xl font-bold text-fsel-teal mb-2">99.000đ</p>
            <p class="text-sm text-gray-400 mb-4">Truy cập đầy đủ tất cả khóa học trong 30 ngày</p>
            <button class="w-full py-3 rounded-xl bg-fsel-teal text-fsel-dark font-semibold hover:bg-fsel-teal/90 transition-colors">Chọn gói này</button>
        </div>
        <div class="card-dark p-6 border-fsel-gold/30 hover:border-fsel-gold/60 transition-colors relative">
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-fsel-gold text-fsel-dark text-xs font-bold px-3 py-0.5 rounded-full">TIẾT KIỆM 40%</span>
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-fsel-gold/20 flex items-center justify-center">
                <svg class="w-7 h-7 text-fsel-gold" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Gia hạn 6 tháng</h3>
            <p class="text-3xl font-bold text-fsel-gold mb-2">349.000đ</p>
            <p class="text-sm text-gray-400 mb-4">Truy cập 180 ngày + Bonus 100 coins miễn phí</p>
            <button class="w-full py-3 rounded-xl bg-gradient-to-r from-fsel-gold to-yellow-500 text-fsel-dark font-semibold hover:opacity-90 transition-opacity">Chọn gói này</button>
        </div>
    </div>
</div>
@endsection
