{{-- Activity Type: Assignment Submission --}}
<div class="space-y-6">
    {{-- Assignment Instructions --}}
    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-rose-400 uppercase tracking-wider">📋 Hướng dẫn bài tập</span>
            @if($activity->passing_grade)
                <span class="text-[10px] text-gray-400 font-mono">Điểm đạt: {{ $activity->passing_grade }}/100</span>
            @endif
        </div>
        <div class="text-xs text-gray-300 leading-relaxed whitespace-pre-line">
            {{ $content['instructions'] ?? $activity->description ?? 'Hãy nộp bài tập theo yêu cầu bên dưới.' }}
        </div>
        <div class="flex items-center gap-4 text-[10px] text-gray-400 pt-1 border-t border-slate-800">
            <span>Định dạng cho phép: <strong class="text-gray-300">{{ $content['allowed_extensions'] ?? 'pdf, docx, zip' }}</strong></span>
            <span>Dung lượng tối đa: <strong class="text-gray-300">{{ $content['max_file_size_mb'] ?? 20 }} MB</strong></span>
            @if($activity->max_attempts)
                <span>Số lần nộp: <strong class="text-gray-300">{{ count($mySubmissions ?? []) }}/{{ $activity->max_attempts }}</strong></span>
            @endif
        </div>
    </div>

    {{-- Submission History --}}
    @if(!empty($mySubmissions) && count($mySubmissions) > 0)
        <div class="space-y-3">
            <h4 class="text-xs font-bold text-white uppercase tracking-wider">📜 Lịch sử nộp bài của bạn</h4>
            <div class="space-y-2">
                @foreach($mySubmissions as $sub)
                    <div class="p-4 bg-slate-900/80 rounded-xl border border-slate-800 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white">Lần nộp #{{ $sub->attempt_number }}</span>
                                @php $info = $sub->getStatusInfo(); @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium bg-{{ $info['color'] }}-500/10 text-{{ $info['color'] }}-300 border border-{{ $info['color'] }}-500/20">
                                    {{ $info['icon'] }} {{ $info['label'] }}
                                </span>
                            </div>
                            <span class="text-[10px] text-gray-500 font-mono">{{ $sub->submitted_at?->format('d/m/Y H:i') }}</span>
                        </div>

                        @if($sub->file)
                            <div class="flex items-center justify-between text-xs bg-slate-950/60 p-2 rounded-lg">
                                <span class="text-gray-300 font-mono">📎 {{ $sub->file->original_name }} ({{ $sub->file->getSizeFormatted() }})</span>
                                <a href="{{ route('assignmentSubmissions.download', $sub->id) }}" class="text-blue-400 hover:underline text-[10px]">Tải file</a>
                            </div>
                        @endif

                        @if($sub->text_content)
                            <p class="text-xs text-gray-300 bg-slate-950/40 p-2 rounded-lg italic">{{ $sub->text_content }}</p>
                        @endif

                        @if($sub->isGraded())
                            <div class="p-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs">
                                <div class="flex items-center justify-between font-bold text-emerald-300">
                                    <span>Điểm: {{ $sub->grade }}/100</span>
                                    <span class="text-[10px] text-gray-400 font-normal">Chấm bởi: {{ $sub->grader->name ?? 'Giáo viên' }}</span>
                                </div>
                                @if($sub->feedback)
                                    <p class="text-xs text-gray-300 mt-1">Nhận xét: {{ $sub->feedback }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Submission Form --}}
    @php
        $canSubmit = !$activity->max_attempts || count($mySubmissions ?? []) < $activity->max_attempts;
    @endphp

    @if($isTrialMode ?? false)
        <div class="p-5 bg-teal-500/10 rounded-2xl border border-teal-500/30 text-center space-y-2">
            <div class="text-sm font-bold text-teal-300">✨ Chế độ học thử nghiệm</div>
            <p class="text-xs text-gray-300">Bạn đang trải nghiệm học thử hoạt động này. Hãy <a href="{{ route('courses.show', $course->id) }}" class="underline font-bold text-teal-300 hover:text-white">ghi danh khóa học</a> để nộp bài tập chính thức và nhận điểm đánh giá từ giáo viên!</p>
        </div>
    @elseif($canSubmit && $activity->isAvailable())
        <form action="{{ route('activities.submitAssignment', $activity->id) }}" method="POST" enctype="multipart/form-data" class="bg-slate-900 p-5 rounded-2xl border border-slate-800 space-y-4">
            @csrf
            <h4 class="text-xs font-bold text-white uppercase tracking-wider">📤 Nộp bài tập mới</h4>

            <div>
                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Tải lên tệp bài làm:</label>
                <input type="file" name="submission_file" class="block w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-500/20 file:text-rose-300 hover:file:bg-rose-500/30">
            </div>

            <div>
                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Ghi chú hoặc câu trả lời (nếu có):</label>
                <textarea name="text_content" rows="3" placeholder="Nhập ghi chú gửi cho giáo viên hoặc câu trả lời trực tiếp..." class="login-input !py-2 text-xs"></textarea>
            </div>

            <div class="pt-2 text-center">
                <button type="submit" class="btn-primary !w-auto !py-2.5 px-8 text-sm bg-rose-600 hover:bg-rose-500">
                    Gửi bài tập 📤
                </button>
            </div>
        </form>
    @elseif(!$canSubmit)
        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 text-center text-xs text-gray-400">
            Bạn đã đạt tối đa số lần nộp bài cho hoạt động này.
        </div>
    @else
        <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 text-center text-xs text-gray-400">
            Bài tập hiện đã đóng hoặc chưa đến hạn mở.
        </div>
    @endif
</div>
