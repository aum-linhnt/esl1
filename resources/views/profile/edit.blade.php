@extends('layouts.app')

@section('content')
<div class="w-full space-y-6 pb-12" style="font-family: inherit;">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Hồ sơ cá nhân</h1>
            <p class="text-xs text-gray-400 mt-0.5">Quản lý thông tin tài khoản, lộ trình học tập và bảo mật</p>
        </div>

        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-gray-300 hover:text-white border border-slate-700 transition-colors">
            ← Về Trang chủ
        </a>
    </div>

    {{-- Alert Messages --}}
    @if(session('status') === 'profile-updated')
        <div class="p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <span>✅</span>
            <span>Cập nhật thông tin hồ sơ thành công!</span>
        </div>
    @endif

    @if(session('status') === 'password-updated')
        <div class="p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <span>🔒</span>
            <span>Đổi mật khẩu mới thành công!</span>
        </div>
    @endif

    {{-- 1. CLEAN HORIZONTAL PROFILE HEADER CARD --}}
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px;">
        
        {{-- Left: Avatar + User Info (Always Horizontal) --}}
        <div style="display: flex; align-items: center; gap: 20px; flex: 1; min-width: 280px;">
            {{-- Avatar --}}
            <div style="position: relative; flex-shrink: 0;">
                <div style="width: 76px; height: 76px; border-radius: 18px; background: linear-gradient(135deg, #6366f1, #a855f7, #14b8a6); padding: 2px; box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);">
                    <div style="width: 100%; height: 100%; border-radius: 16px; background: #0f172a; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="{{ $user->avatar_url }}" 
                             alt="{{ $user->name }}" 
                             style="width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                    </div>
                </div>
                <span style="position: absolute; bottom: -2px; right: -2px; width: 14px; height: 14px; background: #10b981; border: 3px solid #0f172a; border-radius: 50%;" title="Đang hoạt động"></span>
            </div>

            {{-- Info Details --}}
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h2 class="text-xl font-bold text-white" style="margin: 0;">{{ $user->name }}</h2>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 20px; background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3);">
                        {{ ucfirst($user->role ?? 'student') }}
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 12px; color: #94a3b8;">
                    <span>📧 {{ $user->email }}</span>
                    @if($user->phone)
                        <span>·</span>
                        <span>📞 {{ $user->phone }}</span>
                    @endif
                    @if($user->city)
                        <span>·</span>
                        <span>📍 {{ $user->city }}</span>
                    @endif
                </div>

                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
                    <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: rgba(20, 184, 166, 0.15); color: #2dd4bf; border: 1px solid rgba(20, 184, 166, 0.25);">
                        Trình độ: <strong>{{ $user->current_level ?? 'A1' }}</strong>
                    </span>
                    <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25);">
                        Mục tiêu: <strong>{{ $user->target_level ?? 'B1' }}</strong>
                    </span>
                </div>
            </div>
        </div>

        {{-- Right: 2 Compact Stats Badges --}}
        <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
            <div style="padding: 12px 18px; border-radius: 14px; background: rgba(15, 23, 42, 0.8); border: 1px solid #1e293b; min-width: 130px;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                    <span style="font-size: 14px;">🔥</span>
                    <span style="font-size: 10px; font-weight: 700; color: #f97316; text-transform: uppercase;">Chuỗi học</span>
                </div>
                <div style="font-size: 18px; font-weight: 800; color: #ffffff;">{{ $user->streak_count ?? 1 }} Ngày</div>
            </div>

            <div style="padding: 12px 18px; border-radius: 14px; background: rgba(15, 23, 42, 0.8); border: 1px solid #1e293b; min-width: 130px;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                    <span style="font-size: 14px; color: #eab308;">⚡</span>
                    <span style="font-size: 10px; font-weight: 700; color: #2dd4bf; text-transform: uppercase;">Tích lũy</span>
                </div>
                <div style="font-size: 18px; font-weight: 800; color: #ffffff;">{{ number_format($user->xp ?? 100) }} XP</div>
            </div>
        </div>

    </div>

    {{-- 2. MAIN 2-COLUMN GRID (Personal Info Form + Password/Security) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left 2 Columns: Personal & Academic Info Form --}}
        <div class="lg:col-span-2 p-6 sm:p-7 rounded-2xl bg-slate-900 border border-slate-800 space-y-6 shadow-xl">
            
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #1e293b; padding-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">📝</span>
                    <div>
                        <h3 class="text-base font-bold text-white" style="margin: 0;">Thông tin cá nhân & Học vấn</h3>
                        <p class="text-xs text-gray-400" style="margin: 0;">Cập nhật đầy đủ thông tin để nhận hỗ trợ học tập tốt nhất</p>
                    </div>
                </div>
                <span class="text-xs text-gray-500 font-mono">* Bắt buộc</span>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4" x-data="{
                avatarPreview: '{{ $user->avatar ? $user->avatar_url : '' }}',
                handleAvatarChange(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.avatarPreview = URL.createObjectURL(file);
                    }
                }
            }">
                @csrf
                @method('PATCH')

                {{-- Avatar Upload Row --}}
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-col sm:flex-row items-center gap-4">
                    <div class="relative w-16 h-16 rounded-2xl overflow-hidden bg-slate-900 border-2 border-indigo-500/40 flex-shrink-0 flex items-center justify-center">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="Avatar preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview">
                            <span class="text-xl font-bold text-indigo-400 font-mono">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        </template>
                    </div>

                    <div class="flex-1 text-center sm:text-left space-y-1">
                        <label class="block text-xs font-bold text-gray-200">Ảnh đại diện (Avatar):</label>
                        <p class="text-[11px] text-gray-400">Chọn ảnh chân dung hoặc avatar định dạng JPG, PNG, WEBP (tối đa 5MB)</p>
                        <input type="file" name="avatar" accept="image/*" @change="handleAvatarChange($event)"
                               class="block w-full text-xs text-gray-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600/30 file:text-indigo-200 hover:file:bg-indigo-600/50 cursor-pointer pt-1">
                        @error('avatar') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Row 1: Name & Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Họ và tên <span class="text-red-400">*</span>:
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('name') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>


                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Địa chỉ Email <span class="text-red-400">*</span>:
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                               class="login-input text-xs w-full font-mono bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('email') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Row 2: Phone, Birthday, Gender --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Số điện thoại:
                        </label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="VD: 0912345678" 
                               class="login-input text-xs w-full font-mono bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('phone') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Ngày sinh:
                        </label>
                        <input type="date" name="birthday" value="{{ old('birthday', $user->birthday ? $user->birthday->format('Y-m-d') : '') }}" 
                               class="login-input text-xs w-full font-mono bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('birthday') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Giới tính:
                        </label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: '{{ old('gender', $user->gender ?? '') }}',
                            options: {
                                '': '-- Chọn giới tính --',
                                'male': 'Nam',
                                'female': 'Nữ',
                                'other': 'Khác'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="gender" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500 flex items-center justify-between cursor-pointer text-left !py-2.5">
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
                        @error('gender') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Row 3: Address & City --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Địa chỉ thường trú:
                        </label>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}" placeholder="Số nhà, tên đường, phường/xã..." 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('address') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Tỉnh / Thành phố:
                        </label>
                        <input type="text" name="city" value="{{ old('city', $user->city) }}" placeholder="VD: Hà Nội, TP. HCM, Đà Nẵng..." 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('city') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Row 4: School & Target Level --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Trường học / Nơi làm việc:
                        </label>
                        <input type="text" name="school_workplace" value="{{ old('school_workplace', $user->school_workplace) }}" placeholder="VD: THPT Chuyên / ĐH Quốc Gia HN..." 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('school_workplace') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                            Mục tiêu trình độ CEFR:
                        </label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: '{{ old('target_level', $user->target_level ?? 'B1') }}',
                            options: {
                                'A1': 'A1 - Bắt đầu / Mất gốc',
                                'A2': 'A2 - Sơ cấp cơ bản',
                                'B1': 'B1 - Giao tiếp tự tin (IELTS 4.5-5.0)',
                                'B2': 'B2 - Trung cao cấp (IELTS 5.5-6.5)',
                                'C1': 'C1 - Thành thạo nâng cao (IELTS 7.0-8.0)',
                                'C2': 'C2 - Bậc thầy chuyên nghiệp'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="target_level" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs w-full font-bold font-mono bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500 flex items-center justify-between cursor-pointer text-left !py-2.5">
                                <span x-text="options[selected] || selected" class="text-white truncate"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
                        @error('target_level') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Row 5: Social Link --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                        Liên kết Facebook cá nhân:
                    </label>
                    <input type="text" name="facebook_url" value="{{ old('facebook_url', $user->facebook_url) }}" placeholder="https://facebook.com/username..." 
                           class="login-input text-xs w-full font-mono bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                    @error('facebook_url') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Row 6: Bio --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1.5">
                        Giới thiệu bản thân & Mục tiêu cá nhân:
                    </label>
                    <textarea name="bio" rows="3" placeholder="Chia sẻ đôi nét về sở thích, động lực hoặc mục tiêu chinh phục Tiếng Anh của bạn..." 
                              class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500 leading-relaxed">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Submit Button --}}
                <div style="border-top: 1px solid #1e293b; padding-top: 16px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-primary !w-auto !py-2.5 px-6 text-xs font-bold shadow-glow-blue flex items-center gap-2 rounded-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Lưu thay đổi thông tin</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right 1 Column: Password & Account Overview --}}
        <div class="space-y-6">

            {{-- 1. Password Change Form --}}
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
                <div style="display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #1e293b; padding-bottom: 12px;">
                    <span style="font-size: 16px;">🔒</span>
                    <div>
                        <h3 class="text-sm font-bold text-white" style="margin: 0;">Đổi mật khẩu</h3>
                        <p class="text-[11px] text-gray-400" style="margin: 0;">Bảo mật tài khoản với mật khẩu mạnh</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('password.update') }}" class="space-y-3.5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Mật khẩu hiện tại:</label>
                        <input type="password" name="current_password" required autocomplete="current-password" placeholder="••••••••" 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('current_password', 'updatePassword') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Mật khẩu mới:</label>
                        <input type="password" name="password" required autocomplete="new-password" placeholder="Tối thiểu 8 ký tự" 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('password', 'updatePassword') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Xác nhận mật khẩu:</label>
                        <input type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Nhập lại mật khẩu mới" 
                               class="login-input text-xs w-full bg-slate-950 border-slate-800 rounded-xl focus:border-indigo-500">
                        @error('password_confirmation', 'updatePassword') <span class="text-red-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div style="padding-top: 6px;">
                        <button type="submit" class="btn-primary !w-full !py-2.5 text-xs font-bold shadow-glow-blue flex items-center justify-center gap-2 rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Cập nhật mật khẩu</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- 2. Study Assistance Card --}}
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 text-xs space-y-3">
                <div class="flex items-center gap-2 text-fsel-teal font-bold text-xs uppercase tracking-wider">
                    <span>💡</span>
                    <span>Cố vấn học tập</span>
                </div>
                <p class="text-gray-400 text-[11px] leading-relaxed">
                    Hệ thống ESL AI sẽ tự động phân tích điểm bài kiểm tra và điều chỉnh các bài tập gợi ý phù hợp với mục tiêu <strong>{{ $user->target_level ?? 'B1' }}</strong> của bạn.
                </p>
                <div style="border-top: 1px solid #1e293b; padding-top: 10px;">
                    <a href="{{ route('enrollments.myCourses') }}" class="text-fsel-accent hover:underline font-bold text-xs inline-block">
                        Truy cập Khóa học của tôi
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
