@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Gamification, Streak & Huy hiệu</h1>
            <p class="text-xs text-gray-400">Quản lý cơ chế thúc đẩy học tập, bảng xếp hạng chuỗi ngày học và cấp phát huy hiệu danh dự</p>
        </div>

        <button onclick="document.getElementById('award-modal').classList.toggle('hidden')" class="btn-primary !w-auto !py-2 px-4 text-xs font-semibold flex items-center gap-2 shadow-glow-blue">
            <span>🏆 Cấp Huy hiệu Thủ công</span>
        </button>
    </div>

    {{-- Top Leaderboards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Streak Leaderboard --}}
        <div class="admin-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span>🔥 Bảng Vàng Streak (Chuỗi ngày học liên tục)</span>
                </h3>
                <span class="text-xs text-orange-400 font-mono font-bold">Top 10</span>
            </div>

            <div class="divide-y divide-slate-800">
                @foreach($topStreakHolders as $idx => $user)
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs {{ $idx === 0 ? 'bg-yellow-500 text-slate-950 font-black' : ($idx === 1 ? 'bg-slate-300 text-slate-950' : ($idx === 2 ? 'bg-amber-600 text-white' : 'bg-slate-800 text-gray-400')) }}">
                                {{ $idx + 1 }}
                            </span>
                            <div>
                                <span class="text-xs font-bold text-white block">{{ $user->name }}</span>
                                <span class="text-[10px] text-gray-400 font-mono">{{ $user->username }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-orange-400 font-mono">{{ $user->streak_count }}</span>
                            <span class="text-xs text-gray-400">ngày liên tiếp</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Coin Richlist --}}
        <div class="admin-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-yellow-300 via-amber-400 to-amber-600 flex items-center justify-center shadow-md shadow-amber-500/20 border border-yellow-200/50 flex-shrink-0">
                        <span class="text-[10px] font-black text-slate-950 font-mono">¢</span>
                    </div>
                    <span>Đại Gia ESL Coins (Số dư tích lũy)</span>
                </h3>
                <span class="text-xs text-fsel-gold font-mono font-bold">Top 10</span>
            </div>

            <div class="divide-y divide-slate-800">
                @foreach($topCoinHolders as $idx => $user)
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs {{ $idx === 0 ? 'bg-yellow-500 text-slate-950 font-black' : ($idx === 1 ? 'bg-slate-300 text-slate-950' : ($idx === 2 ? 'bg-amber-600 text-white' : 'bg-slate-800 text-gray-400')) }}">
                                {{ $idx + 1 }}
                            </span>
                            <div>
                                <span class="text-xs font-bold text-white block">{{ $user->name }}</span>
                                <span class="text-[10px] text-gray-400 font-mono">{{ $user->email }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 font-mono font-bold text-fsel-gold">
                            <span class="text-sm">{{ number_format($user->coins) }}</span>
                            <span class="text-xs">Coins</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Awarded Badges Table --}}
    <div class="admin-card p-6 space-y-4">
        <h3 class="text-base font-bold text-white">Lịch sử Cấp Huy hiệu Gần đây</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-slate-900/80 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Huy hiệu</th>
                        <th class="px-5 py-3">Học viên</th>
                        <th class="px-5 py-3">Mô tả</th>
                        <th class="px-5 py-3">Thời gian cấp</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentBadges as $badge)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ $badge->badge_icon }}</span>
                                    <span class="font-bold text-white">{{ $badge->badge_name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-medium text-white">{{ $badge->user->name ?? 'User #' . $badge->user_id }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-400 text-[11px]">
                                {{ $badge->description }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 font-mono text-[10px]">
                                {{ $badge->unlocked_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.gamification.delete', $badge->id) }}" onsubmit="return confirm('Thu hồi huy hiệu này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-xs p-1">
                                        Thu hồi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-gray-500">Chưa có huy hiệu nào được cấp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Manual Award Badge Modal --}}
    <div id="award-modal" class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="admin-card max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Cấp Huy hiệu cho Học viên</h3>
                <button onclick="document.getElementById('award-modal').classList.add('hidden')" class="text-gray-400 hover:text-white text-lg">&times;</button>
            </div>

            @php
                $firstKey = array_key_first($badgeOptions);
            @endphp
            <form method="POST" action="{{ route('admin.gamification.award') }}" class="space-y-3"
                  x-data="{
                      userId: '{{ $allUsers->first()?->id ?? '' }}',
                      openUser: false,
                      userOptions: {
                          @foreach($allUsers as $u)
                              '{{ $u->id }}': '{{ addslashes($u->name) }} ({{ $u->username }})',
                          @endforeach
                      },
                      badgeKey: '{{ $firstKey }}',
                      openBadge: false,
                      badges: {{ json_encode($badgeOptions) }},
                      get currentBadge() {
                          return this.badges[this.badgeKey] || { name: '', icon: '', desc: '' };
                      }
                  }">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Chọn Học viên:</label>
                    <div class="relative" @click.outside="openUser = false">
                        <input type="hidden" name="user_id" :value="userId" required>
                        <button type="button" @click="openUser = !openUser" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full">
                            <span x-text="userOptions[userId] || 'Chọn học viên...'" class="text-white truncate"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="openUser ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openUser" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                            <template x-for="(lbl, val) in userOptions" :key="val">
                                <div @click="userId = val; openUser = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="userId == val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                    <span x-text="lbl"></span>
                                    <span x-show="userId == val" class="text-emerald-400 font-bold">✓</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Mẫu Huy hiệu:</label>
                    <div class="relative" @click.outside="openBadge = false">
                        <input type="hidden" name="badge_key" :value="badgeKey">
                        <button type="button" @click="openBadge = !openBadge" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full">
                            <span x-text="(currentBadge.icon || '') + ' ' + (currentBadge.name || '')" class="text-white truncate"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="openBadge ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openBadge" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-52 overflow-y-auto">
                            <template x-for="(opt, key) in badges" :key="key">
                                <div @click="badgeKey = key; openBadge = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="badgeKey === key ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                    <span x-text="opt.icon + ' ' + opt.name"></span>
                                    <span x-show="badgeKey === key" class="text-emerald-400 font-bold">✓</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="badge_name" id="b-name" :value="currentBadge.name">
                <input type="hidden" name="badge_icon" id="b-icon" :value="currentBadge.icon">
                <input type="hidden" name="description" id="b-desc" :value="currentBadge.desc">

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" onclick="document.getElementById('award-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg bg-slate-800 text-xs text-gray-300">Hủy</button>
                    <button type="submit" class="btn-primary !w-auto !py-2 px-5 text-xs font-semibold">Cấp ngay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fillBadgeDetails(select) {
    const opt = select.options[select.selectedIndex];
    document.getElementById('b-name').value = opt.getAttribute('data-name');
    document.getElementById('b-icon').value = opt.getAttribute('data-icon');
    document.getElementById('b-desc').value = opt.getAttribute('data-desc');
}
</script>
@endsection
