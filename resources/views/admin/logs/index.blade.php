@extends('layouts.admin')
@section('title', 'Nhật ký thao tác')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white">📋 Nhật ký Thao tác Người dùng</h2>
            <p class="text-sm text-gray-400 mt-1">Theo dõi mọi hành động: đăng nhập, truy cập khóa học, bài học, thi, ghi danh...</p>
        </div>
        <a href="{{ route('admin.logs.export', request()->query()) }}" class="bg-green-600/20 hover:bg-green-600/30 text-green-400 text-xs px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            Xuất CSV
        </a>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-blue">{{ number_format($todayCount) }}</p>
            <p class="text-xs text-gray-400 mt-1">Hôm nay</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-teal">{{ number_format($weekCount) }}</p>
            <p class="text-xs text-gray-400 mt-1">7 ngày qua</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-purple">{{ number_format($totalCount) }}</p>
            <p class="text-xs text-gray-400 mt-1">Tổng cộng</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-accent">{{ count($todayActions) }}</p>
            <p class="text-xs text-gray-400 mt-1">Loại HĐ hôm nay</p>
        </div>
    </div>

    {{-- Action Distribution Today + Top Users --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Today's Action Distribution --}}
        <div class="card-dark p-4">
            <h3 class="text-sm font-semibold text-white mb-3">📊 Phân bố hành động hôm nay</h3>
            @if(count($todayActions) > 0)
                <div class="space-y-2">
                    @php $maxActionCount = max($todayActions) ?: 1; @endphp
                    @foreach($todayActions as $action => $count)
                        <div class="flex items-center gap-2">
                            <span class="text-xs w-28 truncate {{ \App\Models\UserActionLog::actionLabels()[$action] ? 'text-gray-300' : 'text-gray-500' }}">
                                {{ $actionLabels[$action] ?? $action }}
                            </span>
                            <div class="flex-1 bg-slate-800 rounded-full h-4 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-fsel-blue to-fsel-teal rounded-full transition-all duration-500"
                                     style="width: {{ ($count / $maxActionCount) * 100 }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-8 text-right">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500 text-center py-4">Chưa có dữ liệu hôm nay</p>
            @endif
        </div>

        {{-- Top Active Users Today --}}
        <div class="card-dark p-4">
            <h3 class="text-sm font-semibold text-white mb-3">🏆 Người dùng hoạt động nhất hôm nay</h3>
            @if($topUsersToday->count() > 0)
                <div class="space-y-2">
                    @foreach($topUsersToday as $i => $entry)
                        <div class="flex items-center gap-3 py-1.5 {{ $i === 0 ? '' : 'border-t border-fsel-border/30' }}">
                            <span class="text-lg {{ $i === 0 ? 'text-yellow-400' : ($i === 1 ? 'text-gray-300' : 'text-amber-600') }}">
                                {{ $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '')) }}
                            </span>
                            <div class="w-7 h-7 rounded-full bg-fsel-blue/20 flex items-center justify-center text-xs font-bold text-fsel-blue">
                                {{ strtoupper(substr($entry->user->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-white truncate">{{ $entry->user->name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-gray-500 truncate">{{ $entry->user->email ?? '' }}</p>
                            </div>
                            <span class="text-xs font-bold text-fsel-teal">{{ $entry->action_count }} HĐ</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500 text-center py-4">Chưa có dữ liệu</p>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-dark p-4 mb-6 relative z-30">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            {{-- Action Type Filter --}}
            <div :class="open ? 'relative z-50' : 'relative z-20'" class="min-w-[200px]" x-data="{
                open: false,
                selected: '{{ request('action') }}',
                options: {
                    '': 'Tất cả hành động',
                    @foreach($actionLabels as $key => $label)
                        '{{ $key }}': '{{ $label }}',
                    @endforeach
                }
            }" @click.outside="open = false">
                <input type="hidden" name="action" :value="selected">
                <button type="button" @click="open = !open" class="bg-fsel-navy border border-fsel-border rounded-lg text-sm text-white px-3 py-2 flex items-center justify-between cursor-pointer text-left w-full">
                    <span x-text="options[selected] || selected" class="truncate"></span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-64 overflow-y-auto">
                    <template x-for="(lbl, val) in options" :key="val">
                        <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                            <span x-text="lbl"></span>
                            <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Search --}}
            <input type="text" name="search" placeholder="Tìm người dùng / mô tả..." value="{{ request('search') }}" class="bg-fsel-navy border border-fsel-border rounded-lg text-sm text-white px-3 py-2 placeholder-gray-500 min-w-[200px]">

            {{-- Date From --}}
            <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Từ:</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-fsel-navy border border-fsel-border rounded-lg text-sm text-white px-2 py-2">
            </div>

            {{-- Date To --}}
            <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Đến:</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-fsel-navy border border-fsel-border rounded-lg text-sm text-white px-2 py-2">
            </div>

            <button type="submit" class="bg-fsel-blue text-white text-sm px-4 py-2 rounded-lg hover:bg-fsel-blue/80 transition-colors">Lọc</button>
            @if(request()->hasAny(['action', 'search', 'date_from', 'date_to', 'user_id']))
                <a href="{{ route('admin.logs.index') }}" class="text-xs text-gray-400 hover:text-white transition-colors">Xóa bộ lọc</a>
            @endif
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="card-dark overflow-hidden relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-fsel-border text-left">
                        <th class="px-4 py-3 text-gray-400 font-medium text-xs">Thời gian</th>
                        <th class="px-4 py-3 text-gray-400 font-medium text-xs">Người dùng</th>
                        <th class="px-4 py-3 text-gray-400 font-medium text-xs">Hành động</th>
                        <th class="px-4 py-3 text-gray-400 font-medium text-xs">Mô tả</th>
                        <th class="px-4 py-3 text-gray-400 font-medium text-xs">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsel-border/30">
                    @forelse($logs as $log)
                        <tr class="hover:bg-white/[.02] transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs text-gray-300">{{ $log->created_at?->format('d/m/Y') }}</span>
                                <span class="text-[10px] text-gray-500 ml-1">{{ $log->created_at?->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-fsel-blue/20 flex items-center justify-center text-[10px] font-bold text-fsel-blue flex-shrink-0">
                                        {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-white truncate">{{ $log->user->name ?? 'N/A' }}</p>
                                        <p class="text-[10px] text-gray-500 truncate">{{ $log->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $log->action_color }}">
                                    {{ $log->action_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-gray-300 max-w-xs truncate" title="{{ $log->description }}">
                                    {{ $log->description ?? '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-[10px] text-gray-500 font-mono">{{ $log->ip_address ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm">Chưa có dữ liệu nhật ký</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
