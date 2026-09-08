@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Quản lý người dùng</h1>
            <p class="text-xs text-gray-400">Quản lý danh sách tài khoản, vai trò, phân quyền chi tiết, số dư Coins và chuỗi Streak</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-gray-300 hover:text-white border border-slate-700 transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Quản lý Phân quyền (Roles)</span>
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn-primary !w-auto !py-2 px-4 text-xs font-semibold flex items-center gap-1.5 shadow-glow-blue">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Thêm Người dùng mới</span>
            </a>
        </div>
    </div>

    {{-- Advanced Filters Bar --}}
    <div class="admin-card p-4 relative z-30">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            {{-- Search Keyword --}}
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên, email, username..." 
                       class="login-input !py-2 text-xs">
            </div>
            
            {{-- Role Filter --}}
            <div>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selected: '{{ $roleFilter }}',
                    options: {
                        '': '-- Tất cả vai trò --',
                        @foreach($roles as $r)
                            '{{ $r->name }}': '{{ ucfirst($r->name) }}',
                        @endforeach
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="role" :value="selected">
                    <button type="button" @click="open = !open" class="login-input !py-2 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                        <span x-text="options[selected] || selected" class="text-white truncate"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="selected = val; open = false; $nextTick(() => $el.closest('form').submit())" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Status Filter --}}
            <div>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selected: '{{ $statusFilter }}',
                    options: {
                        '': '-- Tất cả trạng thái --',
                        'active': 'Active (Hoạt động)',
                        'trial_expired': 'Trial Expired (Hết hạn thử)',
                        'blocked': 'Blocked (Đã khóa)'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="status" :value="selected">
                    <button type="button" @click="open = !open" class="login-input !py-2 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                        <span x-text="options[selected] || selected" class="text-white truncate"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="selected = val; open = false; $nextTick(() => $el.closest('form').submit())" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Filter & Clear buttons --}}
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary !w-full !py-2 px-4 text-xs font-semibold">
                    Lọc dữ liệu
                </button>
                @if($search || $roleFilter || $statusFilter || $levelFilter)
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 rounded-xl bg-slate-800 text-xs text-gray-400 hover:text-white transition-colors">
                        Xóa
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="admin-card overflow-hidden relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-slate-900/80 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Học viên / User</th>
                        <th class="px-5 py-3.5">Tài khoản & Email</th>
                        <th class="px-5 py-3.5">Vai trò (Role)</th>
                        <th class="px-5 py-3.5">Trạng thái</th>
                        <th class="px-5 py-3.5">Coins</th>
                        <th class="px-5 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center font-bold text-white text-xs shadow-md overflow-hidden flex-shrink-0">
                                        <img src="{{ $u->avatar_url }}" 
                                             alt="{{ $u->name }}" 
                                             class="w-full h-full object-cover"
                                             onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                                    </div>

                                    <div>
                                        <a href="{{ route('admin.users.show', $u->id) }}" class="font-bold text-white hover:text-indigo-400 transition-colors">
                                            {{ $u->name }}
                                        </a>
                                        <div class="text-[10px] text-gray-400 font-mono">
                                            Level: <strong class="text-fsel-teal">{{ $u->current_level }}</strong> · Streak: {{ $u->streak_count }}🔥
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-mono text-fsel-teal font-semibold">{{ $u->username }}</div>
                                <div class="text-[10px] text-gray-400">{{ $u->email }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <form method="POST" action="{{ route('admin.users.updateRole', $u->id) }}">
                                    @csrf
                                    @php
                                        $currentRole = $u->hasRole('admin') ? 'admin' : ($u->hasRole('teacher') ? 'teacher' : ($u->role ?: 'student'));
                                    @endphp
                                    <select name="role" onchange="this.form.submit()" 
                                            class="bg-slate-900 border border-slate-700 rounded pl-2.5 pr-7 py-1 text-xs text-white font-medium cursor-pointer focus:outline-none focus:border-indigo-500 outline-none">
                                        @foreach($roles as $r)
                                            <option value="{{ $r->name }}" class="bg-slate-900 text-white py-1 capitalize" {{ $currentRole === $r->name ? 'selected' : '' }}>
                                                {{ ucfirst($r->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-3.5">
                                @if($u->status === 'active')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active
                                    </span>
                                @elseif($u->status === 'trial_expired')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-yellow-400 bg-yellow-500/10 px-2 py-0.5 rounded-full border border-yellow-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span> Trial Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-400 bg-red-500/10 px-2 py-0.5 rounded-full border border-red-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Blocked
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2" x-data="{ openCoins: false }">
                                    <span class="font-bold text-fsel-gold font-mono">{{ $u->coins }}</span>
                                    
                                    <button @click="openCoins = !openCoins" class="text-[10px] text-gray-400 hover:text-white px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700">
                                        ± Sửa
                                    </button>

                                    <div x-show="openCoins" @click.outside="openCoins = false" x-cloak 
                                         class="absolute z-20 mt-8 bg-slate-900 border border-slate-700 p-3 rounded-xl shadow-2xl flex flex-col gap-2">
                                        <form method="POST" action="{{ route('admin.users.updateCoins', $u->id) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="action" value="add">
                                            <input type="number" name="amount" value="10" min="1" class="w-16 px-2 py-1 bg-slate-800 border border-slate-700 text-xs rounded text-white font-mono">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] px-2 py-1 rounded font-bold">+ Cộng</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.updateCoins', $u->id) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="action" value="subtract">
                                            <input type="number" name="amount" value="10" min="1" class="w-16 px-2 py-1 bg-slate-800 border border-slate-700 text-xs rounded text-white font-mono">
                                            <button type="submit" class="bg-red-600 hover:bg-red-500 text-white text-[10px] px-2 py-1 rounded font-bold">- Trừ</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.users.show', $u->id) }}" title="Xem hồ sơ học tập" class="p-1.5 rounded bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    <a href="{{ route('admin.users.edit', $u->id) }}" title="Chỉnh sửa người dùng" class="p-1.5 rounded bg-slate-800 text-gray-300 hover:text-white transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    @if($u->status === 'trial_expired')
                                        <form method="POST" action="{{ route('admin.users.activate', $u->id) }}">
                                            @csrf
                                            <button type="submit" title="Gia hạn 30 ngày" class="text-[10px] bg-fsel-teal/20 text-fsel-teal hover:bg-fsel-teal/30 px-2 py-1 rounded font-semibold transition-colors">
                                                Gia hạn
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.users.toggleStatus', $u->id) }}">
                                        @csrf
                                        <button type="submit" class="text-[10px] px-2 py-1 rounded font-semibold transition-colors {{ $u->status === 'blocked' ? 'bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30' : 'bg-amber-500/20 text-amber-400 hover:bg-amber-500/30' }}">
                                            {{ $u->status === 'blocked' ? 'Mở' : 'Khóa' }}
                                        </button>
                                    </form>

                                    @if(Auth::user()->id !== $u->id)
                                        <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" onsubmit="return confirm('Xóa vĩnh viễn người dùng {{ $u->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Xóa tài khoản" class="p-1.5 rounded bg-red-500/15 text-red-400 hover:bg-red-500/25 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                Không tìm thấy người dùng nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-800 bg-slate-900/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
