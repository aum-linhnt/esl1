@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-white mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại danh sách khóa học
            </a>
            <h1 class="text-2xl font-bold text-white tracking-tight">
                {{ $course ? 'Cài đặt Khóa học: ' . $course->title : 'Tạo Khóa học Đào tạo Mới' }}
            </h1>
        </div>
    </div>

    {{-- Course Details Form --}}
    <div class="admin-card p-6 lg:p-8">
        <form method="POST" action="{{ $course ? route('admin.courses.update', $course->id) : route('admin.courses.store') }}" enctype="multipart/form-data" class="space-y-5" x-data="{
            thumbPreview: '{{ $course ? $course->thumbnail_url : '' }}',
            handleThumbChange(e) {
                const file = e.target.files[0];
                if (file) {
                    this.thumbPreview = URL.createObjectURL(file);
                }
            }
        }">
            @csrf
            @if($course)
                @method('PUT')
            @endif


            <div class="space-y-4">
                <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Thông tin chung khóa học</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Tên khóa học:</label>
                        <input type="text" name="title" value="{{ old('title', $course?->title) }}" required placeholder="VD: English Basics A1 - Giao tiếp hàng ngày" class="login-input text-xs">
                        @error('title') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Cấp độ CEFR:</label>
                        <div class="relative" x-data="{ 
                            open: false, 
                            selected: '{{ old('level', $course?->level ?? 'A1') }}',
                            levels: {
                                'A1': 'A1 (Cơ bản)',
                                'A2': 'A2 (Sơ cấp)',
                                'B1': 'B1 (Trung cấp)',
                                'B2': 'B2 (Nâng cao)',
                                'C1': 'C1 (Chuyên nghiệp)',
                                'C2': 'C2 (Thành thạo)'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="level" :value="selected">
                            
                            <button type="button" 
                                    @click="open = !open" 
                                    class="login-input text-xs font-bold font-mono flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="levels[selected] || selected" class="text-white"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div x-show="open" 
                                 x-cloak 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute z-50 left-0 right-0 mt-1.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                                <template x-for="(lbl, code) in levels" :key="code">
                                    <div @click="selected = code; open = false"
                                         class="px-3 py-2 text-xs font-mono font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white"
                                         :class="selected === code ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="selected === code" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Đối tượng học viên (Target Audience):</label>
                        <input type="text" name="target_audience" value="{{ old('target_audience', $course?->target_audience) }}" placeholder="VD: Học sinh THCS / Người đi làm mới bắt đầu" class="login-input text-xs">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-300">Ảnh bìa khóa học (Thumbnail):</label>
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-900 border border-slate-700 flex-shrink-0 flex items-center justify-center">
                                <template x-if="thumbPreview">
                                    <img :src="thumbPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!thumbPreview">
                                    <span class="text-xs text-gray-500 font-mono">No pic</span>
                                </template>
                            </div>
                            <div class="flex-1 space-y-1">
                                <input type="file" name="thumbnail_file" accept="image/*" @change="handleThumbChange($event)"
                                       class="block w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-600/30 file:text-indigo-200 hover:file:bg-indigo-600/50 cursor-pointer">
                                <input type="text" name="thumbnail" value="{{ old('thumbnail', $course?->thumbnail) }}" placeholder="Hoặc dán URL: /images/course-a1.png" class="login-input text-xs font-mono !py-1">
                            </div>
                        </div>
                    </div>

                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Mô tả tổng quan & Mục tiêu khóa học:</label>
                    <textarea name="description" rows="4" placeholder="Khóa học cung cấp kiến thức nền tảng về..." class="login-input text-xs leading-relaxed">{{ old('description', $course?->description) }}</textarea>
                </div>
            </div>

            {{-- Course Enrollment Methods & Access Settings (Moodle style) --}}
            <div class="space-y-4 pt-4 border-t border-slate-800" x-data="{ allowSelf: {{ old('allow_self_enrollment', $course?->allow_self_enrollment ?? true) ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Cấu hình Ghi danh & Quyền truy cập (Enrolment Methods)</h3>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Moodle Standard</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- 1. Enrollment Method --}}
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                        <label class="block text-xs font-bold text-white mb-1">Phương thức ghi danh khóa học:</label>
                        <div class="space-y-2">
                            <label class="p-2.5 rounded-xl border text-xs cursor-pointer transition-all flex items-start gap-2.5"
                                   :class="allowSelf ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/40' : 'bg-slate-900/80 border-slate-800 text-gray-400 hover:border-slate-700'">
                                <input type="radio" name="allow_self_enrollment" value="1" x-model="allowSelf" :value="true" class="mt-0.5 text-indigo-600 bg-slate-800 border-slate-700">
                                <div>
                                    <span class="font-bold text-[11px] block text-white">🟢 Cho phép Học viên tự ghi danh (Self-enrollment)</span>
                                    <span class="text-[10px] text-gray-400 block">Học viên có thể bấm "Ghi danh ngay" tại cổng khóa học.</span>
                                </div>
                            </label>

                            <label class="p-2.5 rounded-xl border text-xs cursor-pointer transition-all flex items-start gap-2.5"
                                   :class="!allowSelf ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/40' : 'bg-slate-900/80 border-slate-800 text-gray-400 hover:border-slate-700'">
                                <input type="radio" name="allow_self_enrollment" value="0" x-model="allowSelf" :value="false" class="mt-0.5 text-indigo-600 bg-slate-800 border-slate-700">
                                <div>
                                    <span class="font-bold text-[11px] block text-white">🔒 Chỉ Quản trị viên / Giáo viên ghi danh (Manual only)</span>
                                    <span class="text-[10px] text-gray-400 block">Đóng cổng tự ghi danh. Học viên chỉ được vào học khi Admin gán quyền thủ công.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 2. Enrollment Key & Default Duration --}}
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                        <div x-show="allowSelf" x-cloak>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Mật khẩu ghi danh (Enrollment Key - Tùy chọn):</label>
                            <input type="text" name="enrollment_key" value="{{ old('enrollment_key', $course?->enrollment_key) }}" placeholder="Để trống nếu không yêu cầu mật khẩu" class="login-input text-xs font-mono">
                            <span class="text-[10px] text-gray-500 mt-1 block">Nếu đặt, học viên phải nhập đúng mật khẩu mới tự ghi danh được.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">Thời hạn học mặc định (Số ngày):</label>
                            <input type="number" name="enrollment_duration_days" value="{{ old('enrollment_duration_days', $course?->enrollment_duration_days) }}" min="1" placeholder="VD: 90 (để trống = Vô thời hạn)" class="login-input text-xs font-mono">
                            <span class="text-[10px] text-gray-500 mt-1 block">Tự động hết hạn sau số ngày này tính từ lúc ghi danh.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grading Scale & Assessment Configuration --}}
            <div class="space-y-4 pt-4 border-t border-slate-800" x-data="{ selectedScale: '{{ old('grading_scale', $course?->grading_scale ?? 'scale_100') }}' }">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Cấu hình Thang điểm & Tiêu chuẩn Đánh giá (Grading Scale)</h3>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 border border-purple-500/30">Multi-Scale</span>
                </div>

                {{-- Grading Scale Radio Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @php
                        $scales = \App\Models\Course::$gradingScales;
                    @endphp
                    @foreach($scales as $code => $meta)
                        <label class="p-3 rounded-xl border text-xs cursor-pointer transition-all flex items-start gap-2.5"
                               :class="selectedScale === '{{ $code }}' ? 'bg-purple-600/15 border-purple-500 text-white ring-1 ring-purple-500/40' : 'bg-slate-950/60 border-slate-800 text-gray-400 hover:border-slate-700'">
                            <input type="radio" name="grading_scale" value="{{ $code }}" x-model="selectedScale" class="mt-0.5 text-purple-600 bg-slate-800 border-slate-700">
                            <div class="flex-1">
                                <span class="font-bold text-[11px] block text-white">{{ $meta['icon'] }} {{ $meta['short'] }}</span>
                                <span class="text-[10px] text-gray-400 block leading-relaxed mt-0.5">{{ $meta['desc'] }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Passing Grade --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                        <label class="block text-xs font-bold text-white mb-1">Điểm chuẩn qua môn (Passing Grade):</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="passing_grade" value="{{ old('passing_grade', $course?->passing_grade ?? 50) }}" min="0" max="100" step="1" class="login-input text-xs font-mono w-28">
                            <span class="text-xs text-gray-500">/ 100 (điểm gốc)</span>
                        </div>
                        <span class="text-[10px] text-gray-500 mt-1.5 block">Học viên cần đạt tối thiểu mức điểm này để xếp loại "Đạt" và nhận chứng chỉ.</span>
                    </div>

                    {{-- Scale Preview --}}
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                        <p class="text-xs font-bold text-white mb-2">Bảng quy đổi tham khảo:</p>
                        <div class="space-y-1 text-[10px] text-gray-400 font-mono">
                            <template x-if="selectedScale === 'scale_100'">
                                <div class="space-y-0.5">
                                    <p>90-100%: <span class="text-emerald-400 font-bold">A (Xuất sắc)</span></p>
                                    <p>80-89%: <span class="text-blue-400 font-bold">B (Giỏi)</span></p>
                                    <p>70-79%: <span class="text-yellow-400 font-bold">C (Khá)</span></p>
                                    <p>60-69%: <span class="text-orange-400 font-bold">D (Trung bình)</span></p>
                                    <p>&lt;60%: <span class="text-red-400 font-bold">F (Yếu)</span></p>
                                </div>
                            </template>
                            <template x-if="selectedScale === 'scale_10'">
                                <div class="space-y-0.5">
                                    <p>9.0-10: <span class="text-emerald-400 font-bold">A+ (Xuất sắc)</span></p>
                                    <p>8.0-8.9: <span class="text-blue-400 font-bold">A (Giỏi)</span></p>
                                    <p>6.5-7.9: <span class="text-yellow-400 font-bold">B (Khá)</span></p>
                                    <p>5.0-6.4: <span class="text-orange-400 font-bold">C (Trung bình)</span></p>
                                    <p>&lt;5.0: <span class="text-red-400 font-bold">D/F (Yếu/Kém)</span></p>
                                </div>
                            </template>
                            <template x-if="selectedScale === 'scale_4'">
                                <div class="space-y-0.5">
                                    <p>3.7-4.0: <span class="text-emerald-400 font-bold">A/A- (Xuất sắc)</span></p>
                                    <p>2.7-3.3: <span class="text-blue-400 font-bold">B+/B/B- (Khá giỏi)</span></p>
                                    <p>1.7-2.3: <span class="text-yellow-400 font-bold">C+/C/C- (Trung bình)</span></p>
                                    <p>1.0-1.3: <span class="text-orange-400 font-bold">D+/D (Yếu)</span></p>
                                    <p>0.0: <span class="text-red-400 font-bold">F (Không đạt)</span></p>
                                </div>
                            </template>
                            <template x-if="selectedScale === 'scale_ielts'">
                                <div class="space-y-0.5">
                                    <p>Band 8.5-9.0: <span class="text-emerald-400 font-bold">Expert User</span></p>
                                    <p>Band 7.0-8.0: <span class="text-blue-400 font-bold">Very Good / Good</span></p>
                                    <p>Band 5.5-6.5: <span class="text-yellow-400 font-bold">Competent User</span></p>
                                    <p>Band 4.0-5.0: <span class="text-orange-400 font-bold">Modest / Limited</span></p>
                                    <p>Band 1.0-3.5: <span class="text-red-400 font-bold">Extremely Limited</span></p>
                                </div>
                            </template>
                            <template x-if="selectedScale === 'scale_pass_fail'">
                                <div class="space-y-0.5">
                                    <p>≥ Điểm chuẩn: <span class="text-emerald-400 font-bold">ĐẠT (Pass)</span></p>
                                    <p>&lt; Điểm chuẩn: <span class="text-red-400 font-bold">CHƯA ĐẠT (Fail)</span></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Course Visibility & Certification Settings --}}
            <div class="space-y-4 pt-4 border-t border-slate-800">
                <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Cài đặt Hiển thị & Chứng nhận</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Thứ tự hiển thị:</label>
                        <input type="number" name="order" value="{{ old('order', $course?->order ?? 1) }}" min="0" class="login-input text-xs font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 mb-1">Tên Huy hiệu trao tặng khi xong:</label>
                        <input type="text" name="badge_reward" value="{{ old('badge_reward', $course?->badge_reward) }}" placeholder="VD: Bậc Thầy A1" class="login-input text-xs">
                    </div>

                    <div class="flex flex-col justify-center space-y-2 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-white">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $course?->is_published ?? true) ? 'checked' : '' }} class="rounded bg-slate-800 border-slate-700 text-indigo-600">
                            <span class="font-bold text-emerald-400">👁️ Xuất bản (Hiện trên cổng học viên)</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-white">
                            <input type="checkbox" name="certificate_enabled" value="1" {{ old('certificate_enabled', $course?->certificate_enabled ?? true) ? 'checked' : '' }} class="rounded bg-slate-800 border-slate-700 text-indigo-600">
                            <span>🏆 Cấp chứng chỉ hoàn thành</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Submit Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                @if($course)
                    <a href="{{ route('admin.courses.show', $course->id) }}" class="text-xs text-fsel-teal hover:underline flex items-center gap-1 font-semibold">
                        <span>Vào soạn giáo trình</span>
                    </a>
                @else
                    <div></div>
                @endif

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.courses.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white">
                        Hủy
                    </a>
                    <button type="submit" class="btn-primary !w-auto !py-2.5 px-6 text-xs font-semibold shadow-glow-blue">
                        {{ $course ? 'Lưu thay đổi' : 'Tạo khóa học & Soạn giáo trình' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
