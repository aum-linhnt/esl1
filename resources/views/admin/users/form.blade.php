@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-white mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại danh sách người dùng
            </a>
            <h1 class="text-2xl font-bold text-white tracking-tight">
                {{ $user ? 'Chỉnh sửa Người dùng: ' . $user->name : 'Tạo Tài khoản Người dùng Mới' }}
            </h1>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="admin-card p-6 lg:p-8">
        <form method="POST" action="{{ $user ? route('admin.users.update', $user->id) : route('admin.users.store') }}" enctype="multipart/form-data" class="space-y-6" x-data="{
            avatarPreview: '{{ $user?->avatar ? $user->avatar_url : '' }}',
            handleAvatarChange(e) {
                const file = e.target.files[0];
                if (file) {
                    this.avatarPreview = URL.createObjectURL(file);
                }
            }
        }">
            @csrf
            @if($user)
                @method('PUT')
            @endif

            {{-- 1. Basic Account Info --}}
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Thông tin tài khoản cơ bản</h3>

                {{-- Avatar Upload Row --}}
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center gap-4">
                    <div class="relative w-14 h-14 rounded-xl overflow-hidden bg-slate-900 border-2 border-indigo-500/40 flex-shrink-0 flex items-center justify-center">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview">
                            <span class="text-lg font-bold text-indigo-400 font-mono">{{ $user ? strtoupper(substr($user->name, 0, 2)) : '👤' }}</span>
                        </template>
                    </div>

                    <div class="flex-1 space-y-1">
                        <label class="block text-xs font-bold text-gray-200">Ảnh đại diện (Avatar):</label>
                        <input type="file" name="avatar" accept="image/*" @change="handleAvatarChange($event)"
                               class="block w-full text-xs text-gray-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600/30 file:text-indigo-200 hover:file:bg-indigo-600/50 cursor-pointer">
                        @error('avatar') <span class="text-red-400 text-[10px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Họ và tên:</label>
                        <input type="text" name="name" value="{{ old('name', $user?->name) }}" required class="login-input text-xs" placeholder="Nguyễn Văn A">
                        @error('name') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Tên đăng nhập (Username):</label>
                        <input type="text" name="username" value="{{ old('username', $user?->username) }}" required class="login-input text-xs font-mono" placeholder="username123">
                        @error('username') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Địa chỉ Email:</label>
                        <input type="email" name="email" value="{{ old('email', $user?->email) }}" required class="login-input text-xs" placeholder="user@example.com">
                        @error('email') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">
                            Mật khẩu {{ $user ? '(Để trống nếu không đổi)' : '' }}:
                        </label>
                        <input type="password" name="password" {{ $user ? '' : 'required' }} minlength="6" class="login-input text-xs" placeholder="••••••••">
                        @error('password') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>


            {{-- 2. Role, Level & Status Settings --}}
            <div class="space-y-4 pt-4 border-t border-slate-800">
                <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Phân quyền & Trạng thái học tập</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Vai trò chính (Role):</label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: '{{ old('role', $user?->role ?? ($roles->first()?->name ?? 'student')) }}',
                            options: {
                                @foreach($roles as $r)
                                    '{{ $r->name }}': '{{ ucfirst($r->name) }}',
                                @endforeach
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="role" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[selected] || selected" class="text-white capitalize"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Trạng thái tài khoản:</label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: '{{ old('status', $user?->status ?? 'active') }}',
                            options: {
                                'active': 'Active (Hoạt động)',
                                'trial_expired': 'Trial Expired (Hết hạn thử)',
                                'blocked': 'Blocked (Đã khóa)'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="status" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[selected] || selected" class="text-white"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Trình độ CEFR ban đầu:</label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: '{{ old('current_level', $user?->current_level ?? 'A1') }}',
                            options: {
                                'A1': 'Level A1',
                                'A2': 'Level A2',
                                'B1': 'Level B1',
                                'B2': 'Level B2',
                                'C1': 'Level C1',
                                'C2': 'Level C2'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="current_level" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs font-mono font-bold flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[selected] || selected" class="text-white"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-mono font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Số dư ESL Coins:</label>
                        <input type="number" name="coins" value="{{ old('coins', $user?->coins ?? 24) }}" min="0" required class="login-input text-xs font-mono">
                    </div>

                    <div>
                        @if($user)
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Gia hạn thêm ngày học thử:</label>
                            <input type="number" name="extend_trial_days" placeholder="VD: 30 ngày (để trống nếu giữ nguyên)" min="1" class="login-input text-xs">
                        @else
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Số ngày học thử ban đầu:</label>
                            <input type="number" name="trial_days" value="3" min="0" class="login-input text-xs">
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. Custom Direct Permissions --}}
            <div class="space-y-3 pt-4 border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Quyền hạn cấp trực tiếp (Direct Permissions)</h3>
                    <span class="text-[10px] text-gray-500">Mặc định kế thừa quyền theo Vai trò</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    @foreach($permissions as $perm)
                        <label class="flex items-center gap-2 text-xs text-gray-300 hover:text-white cursor-pointer select-none">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                   {{ $user && $user->hasDirectPermission($perm->name) ? 'checked' : '' }}
                                   class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-mono text-[11px]">{{ $perm->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Form Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white transition-colors">
                    Hủy bỏ
                </a>
                <button type="submit" class="btn-primary !w-auto !py-2.5 px-7 text-xs font-semibold shadow-glow-blue">
                    {{ $user ? 'Lưu thay đổi' : 'Tạo người dùng' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
