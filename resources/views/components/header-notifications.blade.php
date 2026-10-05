@php
    $user = Auth::user();
    $unreadNotifsCount = $user ? \App\Services\NotificationService::getUnreadCount($user) : 0;
    $unreadMessagesCount = $user ? \App\Services\MessagingService::getTotalUnreadCount($user) : 0;
    $recentNotifs = $user ? \App\Services\NotificationService::getNotifications($user, 6, false, false) : collect();
@endphp

<div class="flex items-center gap-1 sm:gap-2" x-data="{ notifOpen: false }">

    {{-- 1. MESSAGES ICON BADGE --}}
    <a href="{{ route('messages.index') }}" 
       class="header-notif-btn relative w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white dark:bg-[#141d35] border border-slate-200 dark:border-slate-700/60 hover:border-indigo-500/50 text-slate-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-white transition-all shadow-xs dark:shadow-md group flex items-center justify-center flex-shrink-0"
       title="Tin nhắn & Trò chuyện">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>

        @if($unreadMessagesCount > 0)
            <span class="absolute -top-1 -right-1 min-w-[16px] h-[16px] sm:min-w-[18px] sm:h-[18px] px-1 rounded-full bg-indigo-500 text-white text-[9px] sm:text-[10px] font-bold flex items-center justify-center border-2 border-white dark:border-[#0a0f1d] animate-pulse">
                {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
            </span>
        @endif
    </a>

    {{-- 2. NOTIFICATIONS BELL DROPDOWN --}}
    <div class="relative" @click.outside="notifOpen = false">
        <button @click="notifOpen = !notifOpen" 
                class="header-notif-btn relative w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white dark:bg-[#141d35] border border-slate-200 dark:border-slate-700/60 hover:border-indigo-500/50 text-slate-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-white transition-all shadow-xs dark:shadow-md group flex items-center justify-center flex-shrink-0 cursor-pointer"
                title="Thông báo hệ thống">
            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>

            @if($unreadNotifsCount > 0)
                <span class="absolute -top-1 -right-1 min-w-[16px] h-[16px] sm:min-w-[18px] sm:h-[18px] px-1 rounded-full bg-rose-500 text-white text-[9px] sm:text-[10px] font-bold flex items-center justify-center border-2 border-white dark:border-[#0a0f1d] animate-pulse">
                    {{ $unreadNotifsCount > 99 ? '99+' : $unreadNotifsCount }}
                </span>
            @endif
        </button>

        {{-- Dropdown Menu --}}
        <div x-show="notifOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="header-notif-dropdown fixed left-3 right-3 top-14 sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96 max-w-sm mx-auto bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl dark:shadow-2xl overflow-hidden z-[100]">
            
            {{-- Dropdown Header --}}
            <div class="header-notif-header px-4 py-3 bg-slate-50 dark:bg-[#0b1020] border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900 dark:text-white">Thông báo</span>
                    @if($unreadNotifsCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30">
                            {{ $unreadNotifsCount }} mới
                        </span>
                    @endif
                </div>

                @if($unreadNotifsCount > 0)
                    <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                            Đọc tất cả
                        </button>
                    </form>
                @endif
            </div>

            {{-- Items List --}}
            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                @forelse($recentNotifs as $notif)
                    @php $meta = $notif->icon_meta; @endphp
                    <div class="header-notif-item p-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors flex items-start gap-3 group {{ !$notif->is_read ? 'bg-indigo-50/50 dark:bg-indigo-950/20' : '' }}">
                        <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-sm border {{ $meta['bg'] }}">
                            {{ $meta['emoji'] }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-1">
                                <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate {{ !$notif->is_read ? 'text-indigo-700 dark:text-indigo-200' : '' }}">
                                    {{ $notif->title }}
                                </h5>
                                <span class="text-[9px] text-slate-400 dark:text-gray-500 font-mono flex-shrink-0">
                                    {{ $notif->created_at->diffForHumans(null, true) }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-gray-400 line-clamp-2 mt-0.5 leading-relaxed">
                                {{ $notif->message }}
                            </p>
                            @if($notif->action_url)
                                <a href="{{ $notif->action_url }}" class="inline-block mt-1 text-[10px] text-teal-600 dark:text-fsel-teal hover:underline font-semibold">
                                    Xem chi tiết →
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-400 dark:text-gray-500 text-xs space-y-1">
                        <div class="text-2xl">🔕</div>
                        <p>Không có thông báo mới nào</p>
                    </div>
                @endforelse
            </div>

            {{-- Dropdown Footer --}}
            <div class="p-2.5 bg-slate-50 dark:bg-[#0b1020] border-t border-slate-200 dark:border-slate-800 text-center">
                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 block py-1">
                    Xem tất cả thông báo →
                </a>
            </div>
        </div>
    </div>
</div>
