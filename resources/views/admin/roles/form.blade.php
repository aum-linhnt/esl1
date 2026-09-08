@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-white mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại danh sách vai trò
            </a>
            <h1 class="text-2xl font-bold text-white tracking-tight">
                {{ $role ? 'Thiết lập Quyền hạn: ' . ucfirst($role->name) : 'Tạo Vai trò Mới' }}
            </h1>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="admin-card p-6 lg:p-8">
        <form method="POST" action="{{ $role ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}" class="space-y-6">
            @csrf
            @if($role)
                @method('PUT')
            @endif

            {{-- Role Name --}}
            <div class="max-w-md">
                <label class="block text-xs font-semibold text-gray-300 mb-1">Tên vai trò (Role Name):</label>
                @php
                    $coreRoles = ['admin', 'teacher', 'student', 'course_manager', 'course_teacher', 'course_assistant', 'course_student'];
                    $isCore = $role && in_array($role->name, $coreRoles);
                @endphp
                <input type="text" name="name" value="{{ old('name', $role?->name) }}" required
                       {{ $isCore ? 'readonly' : '' }}
                       class="login-input text-xs font-mono {{ $isCore ? 'opacity-60 cursor-not-allowed' : '' }}"
                       placeholder="VD: content_editor, academic_coordinator">
                @if($isCore)
                    <span class="text-[10px] text-amber-400/90 mt-1 block font-medium">🔒 Tên vai trò cốt lõi được bảo vệ và quản trị tự động trong hệ thống.</span>
                @endif
                @error('name') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
            </div>

            {{-- Grouped Permissions Matrix --}}
            <div class="space-y-4 pt-4 border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Ma trận Quyền hạn Chức năng (Permission Matrix)</h3>
                        <p class="text-[11px] text-gray-400">Đánh dấu tích để cấp quyền tương ứng cho nhóm vai trò này</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($groupedPermissions as $groupTitle => $perms)
                        @if(count($perms) > 0)
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2.5">
                                <h4 class="text-xs font-bold text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    <span>{{ $groupTitle }}</span>
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                                    @foreach($perms as $perm)
                                        <label class="flex items-start gap-2.5 p-2 rounded-lg bg-slate-900/60 hover:bg-slate-800/80 border border-slate-800 text-xs text-gray-300 hover:text-white cursor-pointer select-none transition-colors">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                                   {{ $role && $role->hasPermissionTo($perm->name) ? 'checked' : '' }}
                                                   class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                            <div>
                                                <span class="font-semibold text-xs block text-white">{{ $perm->label ?? $perm->name }}</span>
                                                <span class="font-mono text-[10px] text-gray-500">{{ $perm->name }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('admin.roles.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white transition-colors">
                    Hủy bỏ
                </a>
                <button type="submit" class="btn-primary !w-auto !py-2.5 px-7 text-xs font-semibold shadow-glow-blue">
                    {{ $role ? 'Lưu phân quyền' : 'Tạo vai trò' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
