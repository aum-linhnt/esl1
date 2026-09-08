@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto text-center py-8">
    <div class="card-dark p-8">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-fsel-blue/20 flex items-center justify-center">
            <svg class="w-10 h-10 text-fsel-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        </div>
        <h2 class="text-2xl font-bold text-white mb-3">Mời bạn bè cùng học</h2>
        <p class="text-gray-400 text-sm mb-6">Chia sẻ link giới thiệu bên dưới để mời bạn bè tham gia ESL. Mỗi người đăng ký thành công, bạn sẽ nhận được <span class="text-fsel-gold font-semibold">10 coins</span>!</p>

        <div class="flex gap-2 mb-6" x-data="{ copied: false }">
            <input type="text" value="https://fsel.vn/invite/{{ Auth::user()->username }}" readonly class="login-input text-center text-sm flex-1" id="invite-link">
            <button @click="navigator.clipboard.writeText(document.getElementById('invite-link').value); copied = true; setTimeout(() => copied = false, 2000)" class="btn-primary !w-auto !py-2 px-6 text-sm" x-text="copied ? 'Đã sao chép!' : 'Sao chép'"></button>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="card-dark p-4">
                <p class="text-2xl font-bold text-fsel-teal">0</p>
                <p class="text-[11px] text-gray-500 mt-1">Bạn bè đã mời</p>
            </div>
            <div class="card-dark p-4">
                <p class="text-2xl font-bold text-fsel-gold">0</p>
                <p class="text-[11px] text-gray-500 mt-1">Coins nhận được</p>
            </div>
            <div class="card-dark p-4">
                <p class="text-2xl font-bold text-fsel-accent">0</p>
                <p class="text-[11px] text-gray-500 mt-1">Đang chờ duyệt</p>
            </div>
        </div>
    </div>
</div>
@endsection
