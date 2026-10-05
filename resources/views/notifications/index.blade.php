@extends('layouts.app', ['title' => 'Trung tâm thông báo'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800 shadow-lg">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-2xl shadow-sm">
                🔔
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Trung tâm thông báo</span>
                    @if($unreadCount > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500 text-white animate-pulse">
                            {{ $unreadCount }} mới
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-gray-400 mt-0.5">Cập nhật nhắc nhở học tập, kết quả bài thi, bài tập và tin nhắn mới</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Đọc tất cả</span>
                    </button>
                </form>
            @endif

            @if($notifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.clearAll') }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ thông báo?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-red-500/20 hover:text-red-300 hover:border-red-500/30 text-gray-400 border border-slate-700 text-xs font-semibold transition-all">
                        Xóa tất cả
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-gray-400 hover:text-white hover:bg-slate-800/60' }}">
            Tất cả
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 {{ $filter === 'unread' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-gray-400 hover:text-white hover:bg-slate-800/60' }}">
            <span>Chưa đọc</span>
            @if($unreadCount > 0)
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            @endif
        </a>
    </div>

    {{-- Notifications List --}}
    <div class="space-y-3">
        @forelse($notifications as $n)
            @php $meta = $n->icon_meta; @endphp
            <div class="group p-4 sm:p-5 rounded-2xl border transition-all duration-200 flex items-start gap-4 {{ $n->is_read ? 'bg-slate-900/40 border-slate-800/70 hover:border-slate-700' : 'bg-slate-900/90 border-indigo-500/40 shadow-lg shadow-indigo-500/5' }}">
                
                {{-- Type Icon Badge --}}
                <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center text-lg border {{ $meta['bg'] }}">
                    {{ $meta['emoji'] }}
                </div>

                {{-- Notification Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors {{ !$n->is_read ? 'font-extrabold' : '' }}">
                                {{ $n->title }}
                            </h3>
                            <p class="text-xs text-gray-300 mt-1 leading-relaxed whitespace-pre-line">
                                {{ $n->message }}
                            </p>
                        </div>

                        {{-- Timestamp & Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0 text-right">
                            <span class="text-[11px] text-gray-500 font-mono">
                                {{ $n->created_at->diffForHumans() }}
                            </span>

                            @if(!$n->is_read)
                                <form method="POST" action="{{ route('notifications.markAsRead', $n->id) }}">
                                    @csrf
                                    <button type="submit" title="Đánh dấu đã đọc" class="p-1 rounded-lg text-gray-400 hover:text-indigo-400 hover:bg-slate-800 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Xóa thông báo này" class="p-1 rounded-lg text-gray-500 hover:text-red-400 hover:bg-slate-800 transition-colors opacity-0 group-hover:opacity-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Action Link --}}
                    @if($n->action_url)
                        <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                            <a href="{{ $n->action_url }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-fsel-teal hover:underline">
                                <span>Xem chi tiết nội dung</span>
                                <span>→</span>
                            </a>

                            @if(!$n->is_read)
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-12 text-center space-y-3">
                <div class="text-4xl">📭</div>
                <h3 class="text-base font-bold text-white">Chưa có thông báo nào</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">
                    {{ $filter === 'unread' ? 'Bạn đã đọc hết toàn bộ thông báo! Tuyệt vời.' : 'Khi có bài học mới, chấm điểm hoặc tin nhắn, bạn sẽ nhận được thông báo tại đây.' }}
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
        <div class="pt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
