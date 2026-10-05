@extends('layouts.app', ['title' => 'Tin nhắn & Trò chuyện'])

@section('content')
<div class="h-[calc(100vh-140px)] min-h-[580px] max-h-[880px] flex flex-col md:flex-row bg-[#080d1a] border border-slate-800/80 rounded-3xl overflow-hidden shadow-2xl relative" 
     x-data="chatMessengerApp({{ $activeConversation ? $activeConversation->id : 'null' }})">

    {{-- ========================================================= --}}
    {{-- 1. LEFT SIDEBAR: CONVERSATION LIST & QUICK CONTACTS        --}}
    {{-- ========================================================= --}}
    <div class="w-full md:w-80 lg:w-96 flex-shrink-0 flex flex-col bg-[#090e1e] border-r border-slate-800/80"
         :class="activeChatId && !showMobileList ? 'hidden md:flex' : 'flex'">

        {{-- Sidebar Header --}}
        <div class="p-4 border-b border-slate-800/80 flex items-center justify-between gap-3 bg-[#0a1022]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-teal-400 flex items-center justify-center text-white text-lg shadow-lg shadow-indigo-600/30">
                    💬
                </div>
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                        <span>Tin nhắn</span>
                        @if($totalUnread > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-sm shadow-rose-500/40 animate-pulse">
                                {{ $totalUnread }}
                            </span>
                        @endif
                    </h2>
                    <p class="text-[11px] text-slate-400 font-medium">Hỗ trợ & Trao đổi học tập</p>
                </div>
            </div>

            {{-- New Chat Button --}}
            <button @click="showNewChatModal = true" 
                    title="Soạn tin nhắn mới"
                    class="p-2.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 hover:border-indigo-400 shadow-sm transition-all duration-200 cursor-pointer group">
                <svg class="w-4 h-4 transform group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        </div>

        {{-- Search Input & Filters --}}
        <div class="p-3 border-b border-slate-800/60 bg-[#080d1a]/60 space-y-2.5">
            <div class="relative">
                <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Tìm cuộc hội thoại..." 
                       class="w-full bg-slate-900/90 border border-slate-800 focus:border-indigo-500 rounded-xl pl-9 pr-8 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-2.5 text-slate-500 hover:text-white text-xs">
                    ✕
                </button>
            </div>

            {{-- Quick Filter Pills --}}
            <div class="flex items-center gap-1.5 text-[11px] overflow-x-auto no-scrollbar">
                <button type="button" 
                        @click="filterType = 'all'" 
                        :class="filterType === 'all' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'"
                        class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer flex-shrink-0">
                    Tất cả
                </button>
                @if(auth()->user()->isStudent())
                    <button type="button" 
                            @click="filterType = 'teacher'" 
                            :class="filterType === 'teacher' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'"
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer flex-shrink-0 flex items-center gap-1">
                        <span>👨‍🏫 Giảng viên</span>
                    </button>
                @elseif(auth()->user()->isTeacher())
                    <button type="button" 
                            @click="filterType = 'student'" 
                            :class="filterType === 'student' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'"
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer flex-shrink-0 flex items-center gap-1">
                        <span>🎓 Học viên</span>
                    </button>
                @endif
                <button type="button" 
                        @click="filterType = 'unread'" 
                        :class="filterType === 'unread' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'"
                        class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer flex-shrink-0 flex items-center gap-1">
                    <span>Chưa đọc</span>
                    @if($totalUnread > 0)
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Conversations & Contact Suggestions Scroll Area --}}
        <div class="flex-1 overflow-y-auto p-2 space-y-1 divide-y divide-slate-800/40">
            @forelse($conversations as $c)
                @php
                    $isActive = $activeConversation && $activeConversation->id === $c->id;
                    $recipient = $c->recipient;
                    $unread = $c->unread_count;
                    $roleClass = 'student';
                    if ($recipient) {
                        if ($recipient->isAdmin()) $roleClass = 'admin';
                        elseif ($recipient->isTeacher()) $roleClass = 'teacher';
                    }
                @endphp
                <a href="{{ route('messages.index', ['conversation_id' => $c->id]) }}" 
                   x-show="(!searchQuery || '{{ strtolower(addslashes($c->display_title)) }}'.includes(searchQuery.toLowerCase())) && (filterType === 'all' || (filterType === 'unread' && {{ $unread }} > 0) || (filterType === 'teacher' && '{{ $roleClass }}' === 'teacher') || (filterType === 'student' && '{{ $roleClass }}' === 'student'))"
                   class="flex items-center gap-3 p-3 rounded-2xl transition-all duration-150 cursor-pointer group {{ $isActive ? 'bg-gradient-to-r from-indigo-950/60 via-indigo-900/30 to-transparent border-l-4 border-indigo-500 shadow-md' : 'hover:bg-slate-800/50 border-l-4 border-transparent' }}">
                    
                    {{-- Avatar with Online Indicator & Glow --}}
                    <div class="relative flex-shrink-0">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-500 to-teal-400 p-0.5 overflow-hidden shadow-sm">
                            <img src="{{ $c->display_avatar }}" 
                                 alt="{{ $c->display_title }}" 
                                 class="w-full h-full object-cover rounded-[14px]"
                                 onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-[#090e1e] shadow-sm"></span>
                    </div>

                    {{-- Text Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <h4 class="text-xs font-bold text-white truncate group-hover:text-indigo-300 transition-colors">
                                {{ $c->display_title }}
                            </h4>
                            @if($c->latestMessage)
                                <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">
                                    {{ $c->latestMessage->created_at->diffForHumans(null, true) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[11px] text-slate-400 truncate {{ $unread > 0 ? 'font-semibold text-white' : '' }}">
                                @if($c->latestMessage)
                                    @if($c->latestMessage->sender_id === auth()->id())
                                        <span class="text-indigo-400 font-medium">Bạn: </span>
                                    @endif
                                    {{ $c->latestMessage->body }}
                                @else
                                    <span class="italic text-slate-400">Bắt đầu cuộc trò chuyện...</span>
                                @endif
                            </p>

                            @if($unread > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gradient-to-r from-rose-500 to-pink-500 text-white flex-shrink-0 shadow-sm shadow-rose-500/30">
                                    {{ $unread }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                {{-- If no past conversations, show suggested contacts right in the sidebar so it's never empty! --}}
                <div class="p-3 space-y-3">
                    <div class="p-3 rounded-2xl bg-indigo-950/30 border border-indigo-900/40 text-center space-y-1">
                        <span class="text-2xl">👋</span>
                        <p class="text-xs font-semibold text-white">Chưa có cuộc hội thoại nào</p>
                        <p class="text-[11px] text-slate-400">Chọn người liên hệ bên dưới để bắt đầu trao đổi bài tập ngay!</p>
                    </div>

                    @if($availableUsers->isNotEmpty())
                        <div class="pt-1">
                            <div class="px-2 pb-2 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <span>Gợi ý liên hệ ({{ $availableUsers->count() }})</span>
                            </div>

                            <div class="space-y-1.5">
                                @foreach($availableUsers->take(8) as $suggested)
                                    <div class="flex items-center justify-between gap-2 p-2.5 rounded-2xl bg-slate-900/60 hover:bg-slate-800/80 border border-slate-800/60 transition-all">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-500 to-teal-400 p-0.5 flex-shrink-0 overflow-hidden">
                                                <img src="{{ $suggested->avatar_url }}" alt="{{ $suggested->name }}" class="w-full h-full object-cover rounded-[10px]">
                                            </div>
                                            <div class="min-w-0">
                                                <h5 class="text-xs font-bold text-white truncate">{{ $suggested->name }}</h5>
                                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded uppercase border {{ $suggested->isAdmin() ? 'bg-rose-500/10 text-rose-300 border-rose-500/20' : ($suggested->isTeacher() ? 'bg-amber-500/10 text-amber-300 border-amber-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20') }}">
                                                    {{ $suggested->role === 'teacher' ? 'Giảng viên' : ($suggested->role === 'admin' ? 'Quản trị viên' : 'Học viên') }}
                                                </span>
                                            </div>
                                        </div>
                                        <a href="{{ route('messages.startDirect', $suggested->id) }}" 
                                           class="px-2.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold shadow-sm shadow-indigo-500/20 transition-all flex items-center gap-1 flex-shrink-0 cursor-pointer">
                                            <span>Nhắn tin</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. RIGHT CHAT PANEL: ACTIVE CHAT OR WELCOME DISCOVERY HUB  --}}
    {{-- ========================================================= --}}
    <div class="flex-1 flex flex-col bg-[#0b1124] min-w-0"
         :class="activeChatId && !showMobileList ? 'flex' : 'hidden md:flex'">
        
        @if($activeConversation)
            {{-- CHAT HEADER --}}
            <div class="px-5 py-3.5 border-b border-slate-800/80 flex items-center justify-between gap-3 bg-[#0a1022]/90 backdrop-blur-md">
                <div class="flex items-center gap-3.5 min-w-0">
                    {{-- Mobile Back to List Button --}}
                    <button @click="showMobileList = true" class="md:hidden text-slate-400 hover:text-white p-1 rounded-xl hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <div class="relative flex-shrink-0">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-teal-400 p-0.5 overflow-hidden shadow-md">
                            <img src="{{ $activeConversation->display_avatar }}" 
                                 alt="{{ $activeConversation->display_title }}" 
                                 class="w-full h-full object-cover rounded-[14px]"
                                 onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#0a1022] shadow-sm"></span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-white truncate">
                                {{ $activeConversation->display_title }}
                            </h3>
                            @if($activeConversation->recipient)
                                @php $rec = $activeConversation->recipient; @endphp
                                <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded-md uppercase border {{ $rec->isAdmin() ? 'bg-rose-500/10 text-rose-300 border-rose-500/30' : ($rec->isTeacher() ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30') }}">
                                    {{ $rec->role === 'teacher' ? '👨‍🏫 Giảng viên' : ($rec->role === 'admin' ? '⚡ Quản trị' : '🎓 Học viên') }}
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-emerald-400 flex items-center gap-1.5 mt-0.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Trực tuyến · Sẵn sàng trao đổi</span>
                        </p>
                    </div>
                </div>

                {{-- Header Actions --}}
                <div class="flex items-center gap-2 text-xs">
                    @if($activeConversation->recipient)
                        <span class="text-[11px] text-slate-400 hidden lg:inline font-mono bg-slate-900/80 px-2.5 py-1 rounded-xl border border-slate-800">
                            {{ $activeConversation->recipient->email }}
                        </span>
                    @endif
                    <button @click="showNewChatModal = true" 
                            title="Đổi cuộc trò chuyện khác"
                            class="px-3 py-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/60 transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span class="hidden sm:inline">Liên hệ khác</span>
                    </button>
                </div>
            </div>

            {{-- MESSAGES SCROLL AREA --}}
            <div id="messages-container" 
                 class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-gradient-to-b from-[#0a0f20]/60 via-[#0b1124] to-[#090e1e]">
                
                @php $lastDate = null; @endphp
                @forelse($messages as $msg)
                    @php 
                        $msgDate = $msg->created_at->format('d/m/Y');
                        $isMe = $msg->sender_id === auth()->id(); 
                    @endphp

                    {{-- Date Divider Badge --}}
                    @if($lastDate !== $msgDate)
                        <div class="flex justify-center my-3">
                            <span class="px-3 py-1 rounded-full text-[11px] font-semibold bg-slate-900/90 text-slate-400 border border-slate-800 shadow-sm">
                                {{ $msg->created_at->isToday() ? 'Hôm nay' : ($msg->created_at->isYesterday() ? 'Hôm qua' : $msg->created_at->format('d/m/Y')) }}
                            </span>
                        </div>
                        @php $lastDate = $msgDate; @endphp
                    @endif

                    <div class="flex gap-2.5 items-end {{ $isMe ? 'justify-end' : 'justify-start' }}">
                        @if(!$isMe)
                            <div class="w-8 h-8 rounded-2xl bg-gradient-to-tr from-indigo-500 to-teal-400 p-0.5 overflow-hidden flex-shrink-0 shadow-sm mb-1">
                                <img src="{{ $msg->sender?->avatar_url ?? asset('images/default-avatar.svg') }}" 
                                     alt="{{ $msg->sender?->name }}" 
                                     class="w-full h-full object-cover rounded-[14px]"
                                     onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                            </div>
                        @endif

                        <div class="max-w-[85%] sm:max-w-[70%] space-y-1">
                            @if(!$isMe && $activeConversation->isGroup())
                                <span class="text-[10px] text-slate-400 font-bold block ml-1">{{ $msg->sender?->name }}</span>
                            @endif

                            <div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-md {{ $isMe ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 text-white rounded-br-md shadow-indigo-600/20' : 'bg-slate-900/95 border border-slate-800 text-slate-100 rounded-bl-md shadow-sm' }}">
                                <p class="whitespace-pre-line break-words text-[13px] leading-relaxed">{{ $msg->body }}</p>

                                @if($msg->attachment_url)
                                    <div class="mt-2.5 pt-2.5 border-t {{ $isMe ? 'border-white/20' : 'border-slate-800' }}">
                                        <a href="{{ $msg->attachment_url }}" target="_blank" class="inline-flex items-center gap-2 text-xs font-semibold hover:underline">
                                            <span>📎 {{ $msg->attachment_name ?: 'Tệp đính kèm' }}</span>
                                            <span>↗</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-mono px-1 {{ $isMe ? 'justify-end' : 'justify-start' }}">
                                <span>{{ $msg->created_at->format('H:i') }}</span>
                                @if($isMe)
                                    <span class="text-indigo-400 font-bold">· {{ $msg->is_read ? '✓✓ Đã đọc' : '✓ Đã gửi' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="h-full flex flex-col items-center justify-center text-center p-8 space-y-4">
                        <div class="w-16 h-16 rounded-3xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-3xl shadow-inner">
                            👋
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">Chưa có tin nhắn nào trong cuộc trò chuyện này</h4>
                            <p class="text-xs text-slate-400 max-w-sm mt-1">
                                Gửi lời chào đầu tiên để trao đổi bài tập, hỏi bài giảng hoặc nhận tài liệu hướng dẫn học tập!
                            </p>
                        </div>

                        {{-- Starter prompts --}}
                        <div class="flex flex-wrap justify-center gap-2 pt-2 max-w-md">
                            <button type="button" @click="insertPrompt('Xin chào thầy/cô! Em có một số thắc mắc về bài học vừa qua ạ.')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 hover:text-white transition-colors cursor-pointer">
                                👋 Xin chào thầy/cô!
                            </button>
                            <button type="button" @click="insertPrompt('Thầy/cô cho em xin tài liệu tham khảo thêm cho phần này với ạ.')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 hover:text-white transition-colors cursor-pointer">
                                📚 Xin tài liệu học tập
                            </button>
                            <button type="button" @click="insertPrompt('Em đã hoàn thành bài tập và nộp bài rồi ạ, nhờ thầy/cô xem giúp em nhé!')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 hover:text-white transition-colors cursor-pointer">
                                📝 Đã hoàn thành bài tập
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- MESSAGE COMPOSER DOCK --}}
            <div class="p-3 sm:p-4 border-t border-slate-800/80 bg-[#0a1022] space-y-2">
                {{-- Quick Prompt Chips --}}
                <div class="flex items-center gap-1.5 text-[11px] overflow-x-auto no-scrollbar pb-1">
                    <span class="text-slate-400 text-[10px] uppercase font-bold flex-shrink-0">Gợi ý:</span>
                    <button type="button" @click="insertPrompt('Em chào thầy/cô ạ! 👋')" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 flex-shrink-0 transition-colors">
                        👋 Chào thầy/cô
                    </button>
                    <button type="button" @click="insertPrompt('Em có câu hỏi về bài giảng này ạ ❓')" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 flex-shrink-0 transition-colors">
                        ❓ Hỏi bài giảng
                    </button>
                    <button type="button" @click="insertPrompt('Em muốn xin thêm bài tập bổ trợ 📖')" class="px-2.5 py-1 rounded-lg bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 flex-shrink-0 transition-colors">
                        📖 Xin bài tập bổ trợ
                    </button>
                </div>

                {{-- Input Form --}}
                <form @submit.prevent="sendMessageAjax()" class="flex items-center gap-2">
                    <input type="text" 
                           x-model="messageBody" 
                           x-ref="messageInput"
                           :disabled="sending"
                           placeholder="Nhập nội dung tin nhắn... (Nhấn Enter để gửi)" 
                           class="flex-1 bg-slate-900/95 border border-slate-800 focus:border-indigo-500 rounded-2xl px-4 py-3 text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all disabled:opacity-50">

                    <button type="submit" 
                            :disabled="!messageBody.trim() || sending"
                            class="px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold transition-all shadow-lg shadow-indigo-500/25 flex items-center gap-2 flex-shrink-0 cursor-pointer">
                        <span x-show="!sending">Gửi</span>
                        <span x-show="sending">...</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            </div>
        @else
            {{-- ========================================================= --}}
            {{-- EMPTY STATE: WELCOME & DISCOVERY HUB (STUNNING DESKTOP UI) --}}
            {{-- ========================================================= --}}
            <div class="flex-1 flex flex-col justify-center items-center p-6 md:p-12 overflow-y-auto bg-gradient-to-b from-[#0b1124] via-[#090e1e] to-[#080d1a] relative">
                
                {{-- Decorative Ambient Mesh --}}
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="max-w-2xl w-full text-center space-y-6 relative z-10">
                    
                    {{-- 3D-styled Animated Icon Badge --}}
                    <div class="flex justify-center">
                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-teal-400 p-0.5 shadow-2xl shadow-indigo-500/30 flex items-center justify-center transform hover:scale-105 transition-transform duration-300">
                            <div class="w-full h-full bg-[#0a0f22] rounded-[22px] flex items-center justify-center text-4xl">
                                💬
                            </div>
                        </div>
                    </div>

                    {{-- Hero Headings --}}
                    <div class="space-y-2">
                        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            Hộp thư Trao đổi & Hỗ trợ Học tập
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto leading-relaxed">
                            Kênh kết nối trực tiếp giữa Học viên và Giảng viên. Đặt câu hỏi về bài giảng, giải đáp bài tập và nhận hỗ trợ học tập nhanh chóng.
                        </p>
                    </div>

                    {{-- Role Permission Privacy Card --}}
                    <div class="p-3.5 rounded-2xl bg-indigo-950/40 border border-indigo-500/20 text-left max-w-lg mx-auto flex items-start gap-3 shadow-md">
                        <span class="text-xl flex-shrink-0 mt-0.5">🛡️</span>
                        <div class="text-[11px] leading-relaxed">
                            @if(auth()->user()->isStudent())
                                <span class="font-bold text-indigo-300 block">Kênh trao đổi bảo mật & chuẩn hóa:</span>
                                <span class="text-slate-300">Bạn có thể nhắn tin trực tiếp với tất cả <strong>Giảng viên</strong> phụ trách các khóa học bạn đã ghi danh hoặc liên hệ Ban Quản trị.</span>
                            @elseif(auth()->user()->isTeacher())
                                <span class="font-bold text-amber-300 block">Kênh giảng dạy & hướng dẫn:</span>
                                <span class="text-slate-300">Bạn có thể nhắn tin và giải đáp thắc mắc cho tất cả <strong>Học viên</strong> thuộc các khóa học bạn được phân công.</span>
                            @else
                                <span class="font-bold text-rose-300 block">Toàn quyền Quản trị viên:</span>
                                <span class="text-slate-300">Bạn có thể gửi tin nhắn và điều phối tới bất kỳ người dùng nào trên hệ thống.</span>
                            @endif
                        </div>
                    </div>

                    {{-- Quick Contacts Discovery Grid --}}
                    @if($availableUsers->isNotEmpty())
                        <div class="space-y-3 pt-2 text-left">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    ✨ Người liên hệ khả dụng ({{ $availableUsers->count() }})
                                </h4>
                                <button @click="showNewChatModal = true" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold cursor-pointer">
                                    Xem tất cả →
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($availableUsers->take(4) as $u)
                                    <div class="p-3.5 rounded-2xl bg-slate-900/80 hover:bg-slate-800/90 border border-slate-800/80 hover:border-indigo-500/40 transition-all duration-200 group flex items-center justify-between gap-3 shadow-md">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 to-teal-400 p-0.5 overflow-hidden flex-shrink-0 shadow-sm">
                                                <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-full h-full object-cover rounded-[14px]">
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <h5 class="text-xs font-bold text-white truncate group-hover:text-indigo-300 transition-colors">{{ $u->name }}</h5>
                                                </div>
                                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded uppercase border {{ $u->isAdmin() ? 'bg-rose-500/10 text-rose-300 border-rose-500/20' : ($u->isTeacher() ? 'bg-amber-500/10 text-amber-300 border-amber-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20') }}">
                                                    {{ $u->role === 'teacher' ? '👨‍🏫 Giảng viên' : ($u->role === 'admin' ? '⚡ Quản trị' : '🎓 Học viên') }}
                                                </span>
                                            </div>
                                        </div>

                                        <a href="{{ route('messages.startDirect', $u->id) }}" 
                                           class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-bold transition-all shadow-sm flex items-center gap-1 flex-shrink-0 cursor-pointer">
                                            <span>Nhắn</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- If student has 0 available teachers because not enrolled yet --}}
                        <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 text-center space-y-2">
                            <span class="text-3xl">📚</span>
                            <h4 class="text-sm font-bold text-white">Chưa có Giảng viên phụ trách</h4>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                Hãy đăng ký hoặc ghi danh các khóa học để kết nối và trao đổi trực tiếp với giảng viên nhé!
                            </p>
                            <div class="pt-2">
                                <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all shadow-md">
                                    <span>Khám phá khóa học</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Action CTA Button --}}
                    <div class="pt-2">
                        <button @click="showNewChatModal = true" 
                                class="px-6 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white text-xs sm:text-sm font-bold transition-all shadow-xl shadow-indigo-500/25 flex items-center gap-2.5 mx-auto cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>Soạn tin nhắn mới</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ========================================================= --}}
    {{-- 3. NEW CHAT MODAL (PREMIUM GLASS DIALOG)                  --}}
    {{-- ========================================================= --}}
    <div x-show="showNewChatModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-md p-4"
         @keydown.escape.window="showNewChatModal = false">
        
        <div class="w-full max-w-lg bg-[#0e152e] border border-slate-700/80 rounded-3xl p-6 shadow-2xl space-y-4"
             @click.outside="showNewChatModal = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center text-lg border border-indigo-500/30">
                        ✍️
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>Soạn cuộc trò chuyện mới</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            @if(auth()->user()->isAdmin())
                                Quản trị viên có toàn quyền gửi tin nhắn cho mọi người dùng
                            @elseif(auth()->user()->isTeacher())
                                Học viên thuộc các khóa học bạn phụ trách giảng dạy
                            @else
                                Giảng viên phụ trách các khóa học bạn đã ghi danh
                            @endif
                        </p>
                    </div>
                </div>
                <button @click="showNewChatModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                    ✕
                </button>
            </div>

            {{-- Modal Search Input --}}
            <div class="relative">
                <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" 
                       x-model="contactSearch" 
                       placeholder="Tìm kiếm theo tên hoặc email..." 
                       class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            {{-- Contacts List --}}
            <div class="max-h-72 overflow-y-auto space-y-1.5 divide-y divide-slate-800/40 pr-1">
                @forelse($availableUsers as $u)
                    <div x-show="!contactSearch || '{{ strtolower(addslashes($u->name . ' ' . $u->email)) }}'.includes(contactSearch.toLowerCase())"
                         class="pt-1.5 first:pt-0">
                        <div class="p-3 rounded-2xl hover:bg-slate-800/70 border border-transparent hover:border-slate-700/60 transition-all flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-teal-400 p-0.5 overflow-hidden flex-shrink-0 shadow-sm">
                                    <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-full h-full object-cover rounded-[10px]">
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h5 class="text-xs font-bold text-white truncate">{{ $u->name }}</h5>
                                        <span class="text-[9px] font-mono px-1.5 py-0.2 rounded uppercase border {{ $u->isAdmin() ? 'bg-rose-500/10 text-rose-300 border-rose-500/20' : ($u->isTeacher() ? 'bg-amber-500/10 text-amber-300 border-amber-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20') }}">
                                            {{ $u->role === 'teacher' ? 'Giảng viên' : ($u->role === 'admin' ? 'Quản trị viên' : 'Học viên') }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 truncate block mt-0.5">{{ $u->email }}</span>
                                </div>
                            </div>

                            <a href="{{ route('messages.startDirect', $u->id) }}" 
                               class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all flex items-center gap-1 flex-shrink-0 cursor-pointer">
                                <span>Nhắn tin</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-xs space-y-2">
                        <div class="text-3xl">🔍</div>
                        @if(auth()->user()->isStudent())
                            <p class="font-semibold text-white">Chưa có giảng viên khả dụng</p>
                            <p class="text-slate-400">Bạn chưa ghi danh khóa học nào hoặc khóa học chưa có giảng viên phụ trách.</p>
                        @elseif(auth()->user()->isTeacher())
                            <p class="font-semibold text-white">Chưa có học viên khả dụng</p>
                            <p class="text-slate-400">Chưa có học viên nào ghi danh trong các khóa học bạn phụ trách.</p>
                        @else
                            <p>Chưa có người dùng nào khác trong hệ thống.</p>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js Messenger App Controller --}}
<script>
function chatMessengerApp(convId) {
    return {
        activeChatId: convId,
        showMobileList: false,
        showNewChatModal: false,
        searchQuery: '',
        contactSearch: '',
        filterType: 'all',
        messageBody: '',
        sending: false,

        init() {
            this.scrollToBottom();
            if (this.activeChatId) {
                // Poll for new unread messages
                setInterval(() => {
                    this.pollNewMessages();
                }, 6000);
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('messages-container');
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },

        insertPrompt(text) {
            this.messageBody = text;
            this.$nextTick(() => {
                if (this.$refs.messageInput) {
                    this.$refs.messageInput.focus();
                }
            });
        },

        async sendMessageAjax() {
            const text = this.messageBody.trim();
            if (!text || !this.activeChatId || this.sending) return;

            this.sending = true;

            try {
                const res = await fetch(`{{ url('ajax/messages/conversations') }}/${this.activeChatId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ body: text }),
                });

                const data = await res.json();
                if (data.success && data.message) {
                    this.appendMessage(data.message);
                    this.messageBody = '';
                }
            } catch (err) {
                console.error('Send message failed', err);
            } finally {
                this.sending = false;
                this.scrollToBottom();
            }
        },

        appendMessage(msg) {
            const container = document.getElementById('messages-container');
            if (!container) return;

            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            const bubble = document.createElement('div');
            bubble.className = 'flex gap-2.5 items-end justify-end';
            bubble.innerHTML = `
                <div class="max-w-[85%] sm:max-w-[70%] space-y-1">
                    <div class="px-4 py-3 rounded-2xl text-xs leading-relaxed shadow-md bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 text-white rounded-br-md shadow-indigo-600/20">
                        <p class="whitespace-pre-line break-words text-[13px] leading-relaxed">${this.escapeHtml(msg.body)}</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-mono px-1 justify-end">
                        <span>${timeStr}</span>
                        <span class="text-indigo-400 font-bold">· ✓ Đã gửi</span>
                    </div>
                </div>
            `;
            container.appendChild(bubble);
            this.scrollToBottom();
        },

        async pollNewMessages() {
            if (!this.activeChatId) return;
            try {
                const res = await fetch(`{{ url('ajax/messages/unread-count') }}`);
                const data = await res.json();
            } catch(e) {}
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
}
</script>
@endsection
