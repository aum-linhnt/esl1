@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'system' }">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Vai trò & Phân quyền (RBAC)</h1>
            <p class="text-xs text-gray-400">Quản lý phân quyền chi tiết 2 cấp độ: Toàn hệ thống (System Roles) & Khóa học (Course Context Roles - Chuẩn Moodle)</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.create') }}" class="btn-primary !w-auto !py-2 px-4 text-xs font-semibold flex items-center gap-2 shadow-glow-blue">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tạo vai trò mới</span>
            </a>
        </div>
    </div>

    {{-- Tabs Switcher --}}
    <div class="flex items-center gap-2 p-1 bg-slate-900/90 rounded-2xl border border-slate-800 w-fit">
        <button @click="activeTab = 'system'" 
                :class="activeTab === 'system' ? 'bg-indigo-600 text-white font-bold shadow-md' : 'text-gray-400 hover:text-white'"
                class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2">
            <span>🌐 Vai trò Toàn hệ thống (System Roles)</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-800 text-gray-300 font-mono">{{ $systemRoles->count() }}</span>
        </button>

        <button @click="activeTab = 'course'" 
                :class="activeTab === 'course' ? 'bg-indigo-600 text-white font-bold shadow-md' : 'text-gray-400 hover:text-white'"
                class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2">
            <span>📚 Vai trò trong Khóa học (Course Roles)</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-purple-500/20 text-purple-300 font-mono">{{ $courseRoles->count() }} Core</span>
        </button>
    </div>

    {{-- TAB 1: SYSTEM ROLES & SPATIE PERMISSIONS --}}
    <div x-show="activeTab === 'system'" x-cloak class="space-y-4">
        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-slate-900/80 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 whitespace-nowrap">Tên Vai trò (Role)</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Số người dùng</th>
                            <th class="px-5 py-3.5">Quyền hạn đã cấp (Permissions)</th>
                            <th class="px-5 py-3.5 text-right whitespace-nowrap" style="min-width: 170px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($systemRoles as $role)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white font-mono text-sm capitalize">{{ $role->name }}</span>
                                        @if(in_array($role->name, ['admin', 'teacher', 'student']))
                                            <span class="bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-[9px] font-bold px-1.5 py-0.2 rounded uppercase">System Core</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-500">Guard: {{ $role->guard_name }}</span>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-fsel-teal text-sm">{{ $role->users->count() }}</span>
                                    <span class="text-gray-400 text-xs">tài khoản</span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($role->permissions as $perm)
                                            <span class="bg-slate-800 text-gray-300 border border-slate-700 px-2 py-0.5 rounded text-[10px] font-mono">
                                                {{ $perm->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-500 italic text-[11px]">Chưa được gán quyền hạn nào.</span>
                                        @endforelse
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2" style="white-space: nowrap;">
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" 
                                           style="white-space: nowrap; display: inline-flex;"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-indigo-600 border border-slate-700 hover:border-indigo-500 text-gray-200 hover:text-white text-xs font-semibold shadow-sm transition-all group cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-indigo-400 group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span style="white-space: nowrap;">Cấu hình quyền</span>
                                            <svg class="w-3 h-3 text-gray-500 group-hover:text-white group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>

                                        @if(!in_array($role->name, ['admin', 'teacher', 'student']))
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" onsubmit="return confirm('Xác nhận xóa vai trò {{ $role->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/15 border border-transparent hover:border-red-500/30 transition-all" title="Xóa vai trò">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">Chưa có vai trò nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: COURSE CONTEXT ROLES (MOODLE STANDARD WITH EDIT PERMISSIONS) --}}
    <div x-show="activeTab === 'course'" x-cloak class="space-y-6">
        
        {{-- Intro Notice --}}
        <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/25 flex items-start gap-3 text-xs text-purple-200">
            <span class="text-xl flex-shrink-0">💡</span>
            <div>
                <strong class="font-bold block text-purple-100 mb-0.5">Phân quyền theo ngữ cảnh Khóa học (Course Context Roles):</strong>
                <span>Các vai trò này được áp dụng trong từng khóa học riêng biệt. Bạn có thể bấm <strong>"Cấu hình quyền"</strong> cho từng vai trò khóa học để tùy biến tích chọn các đặc quyền chi tiết y hệt như các vai trò core hệ thống!</span>
            </div>
        </div>

        {{-- Course Roles Table with Permission Management --}}
        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-slate-900/80 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 whitespace-nowrap">Vai trò Khóa học (Course Role)</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Lượt Ghi danh</th>
                            <th class="px-5 py-3.5">Đặc quyền Khóa học đã cấp (Course Capabilities)</th>
                            <th class="px-5 py-3.5 text-right whitespace-nowrap" style="min-width: 170px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($courseRoles as $role)
                            @php
                                $meta = $courseRoleMeta[$role->name] ?? [
                                    'name' => ucfirst(str_replace('_', ' ', $role->name)),
                                    'icon' => '📚',
                                    'badge' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
                                    'desc' => '',
                                    'count' => 0,
                                ];
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xl">{{ $meta['icon'] }}</span>
                                        <div>
                                            <span class="font-bold text-white text-sm block">{{ $meta['name'] }}</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="font-mono text-[10px] text-gray-400">Code: {{ $role->name }}</span>
                                                <span class="bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[9px] font-bold px-1.5 py-0.2 rounded uppercase">Course Core</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-2 max-w-sm">{{ $meta['desc'] }}</p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-fsel-teal text-sm">{{ $meta['count'] }}</span>
                                    <span class="text-gray-400 text-xs">ghi danh</span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($role->permissions as $perm)
                                            <span class="bg-purple-950/50 text-purple-200 border border-purple-500/30 px-2 py-0.5 rounded text-[10px] font-mono">
                                                {{ $perm->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-500 italic text-[11px]">Chưa được gán quyền hạn nào.</span>
                                        @endforelse
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" 
                                       style="white-space: nowrap; display: inline-flex;"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-purple-600 border border-slate-700 hover:border-purple-500 text-gray-200 hover:text-white text-xs font-semibold shadow-sm transition-all group cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-purple-400 group-hover:text-white transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span style="white-space: nowrap;">Cấu hình quyền</span>
                                        <svg class="w-3 h-3 text-gray-500 group-hover:text-white group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">Chưa có vai trò khóa học nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Course Capabilities Matrix Table --}}
        <div class="admin-card overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Ma trận Đặc quyền Khóa học (Course Capabilities Matrix)</h3>
                    <p class="text-xs text-gray-400">So sánh quyền hạn chi tiết giữa các vai trò trong phạm vi từng khóa học</p>
                </div>
                <span class="text-[10px] font-mono font-bold px-2.5 py-1 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    Phân quyền động
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-slate-900/90 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 whitespace-nowrap">Hành động & Đặc quyền trong Khóa học</th>
                            <th class="px-4 py-3.5 text-center whitespace-nowrap">🎓 Học viên</th>
                            <th class="px-4 py-3.5 text-center whitespace-nowrap">🧑‍💼 Trợ giảng</th>
                            <th class="px-4 py-3.5 text-center whitespace-nowrap">👨‍🏫 Giáo viên</th>
                            <th class="px-4 py-3.5 text-center whitespace-nowrap">👑 Quản trị khóa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
                        @php
                            $stRole = $courseRoles->firstWhere('name', 'course_student');
                            $asRole = $courseRoles->firstWhere('name', 'course_assistant');
                            $tcRole = $courseRoles->firstWhere('name', 'course_teacher');
                            $mnRole = $courseRoles->firstWhere('name', 'course_manager');

                            $capList = [
                                'view course content'           => 'Truy cập bài học & học liệu số (Video, Từ vựng, Ngữ pháp)',
                                'submit course activities'      => 'Làm bài tập trắc nghiệm & nộp bài thực hành AI',
                                'bypass lesson locks'           => 'Bỏ qua điều kiện khóa bài (Bypass Lesson Progression Locks)',
                                'grade course submissions'      => 'Chấm điểm bài tập / bài thi nói & viết của học viên',
                                'view course gradebook'         => 'Xem Sổ điểm toàn lớp & Tiến độ tất cả học viên',
                                'manage course curriculum'      => 'Thêm / Sửa / Xóa bài học & học liệu (Soạn giáo trình)',
                                'manage course enrollments'     => 'Ghi danh thủ công, Thay đổi vai trò & Đặt hạn kết thúc',
                                'suspend course participants'   => 'Tạm đình chỉ (Suspend) / Kích hoạt lại quyền truy cập học viên',
                            ];
                        @endphp

                        @foreach($capList as $capKey => $capLabel)
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-5 py-3 font-sans text-white">{{ $capLabel }}</td>
                                
                                <td class="px-4 py-3 text-center">
                                    @if($stRole && $stRole->hasPermissionTo($capKey))
                                        <span class="text-emerald-400 font-bold">✅ Có</span>
                                    @else
                                        <span class="text-gray-500">❌ Không</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($asRole && $asRole->hasPermissionTo($capKey))
                                        <span class="text-emerald-400 font-bold">✅ Có</span>
                                    @else
                                        <span class="text-gray-500">❌ Không</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($tcRole && $tcRole->hasPermissionTo($capKey))
                                        <span class="text-emerald-400 font-bold">✅ Có</span>
                                    @else
                                        <span class="text-gray-500">❌ Không</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($mnRole && $mnRole->hasPermissionTo($capKey))
                                        <span class="text-emerald-400 font-bold">✅ Có</span>
                                    @else
                                        <span class="text-gray-500">❌ Không</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
