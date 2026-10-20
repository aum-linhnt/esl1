<div class="tdash-learner-header"><img src="{{ $learnerSupport['learner']->avatar_url }}" alt=""><div><h3>{{ $learnerSupport['learner']->name }}</h3><p>{{ $course->title }}</p></div><a class="tdash-outline" href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'learners']) }}#details">Tất cả học viên</a></div>
<div class="tdash-learner-metrics">
    <div><small>Điểm khóa học đã lưu</small><strong>{{ $learnerSupport['enrollment']->final_grade === null ? 'Chưa có điểm' : (float) $learnerSupport['enrollment']->final_grade.'%' }}</strong></div>
    <div><small>Bài học đã hoàn thành</small><strong>{{ $learnerSupport['completed_lessons'] }} / {{ $learnerSupport['lessons']->count() }}</strong></div>
    <div><small>Bài tập đang chờ chấm</small><strong>{{ $learnerSupport['pending_assignments'] }}</strong></div>
    <div><small>Lượt hỏi trong {{ $days }} ngày</small><strong>{{ $learnerSupport['questions'] }}</strong></div>
</div>
<section class="tdash-learner-review"><h3>@include('dashboard-v2.icon', ['name' => 'target', 'size' => 20]) Hoạt động cần ôn thêm</h3><p>Dựa trên điểm đã ghi nhận dưới ngưỡng ôn tập: tối thiểu 75%, hoặc ngưỡng đạt của hoạt động nếu cao hơn.</p>
    @forelse($learnerSupport['weak']->take(5) as $item)<div class="tdash-review-item"><div><strong>{{ $item['activity']->title }}</strong><small>{{ $item['lesson']->title }} · {{ $item['source'] }}</small></div><b>{{ $item['score'] }}%</b><a class="tdash-outline" href="{{ route('activities.show', $item['activity']->id) }}">Xem hoạt động</a></div>@empty<p class="tdash-empty">Chưa có kết quả dưới ngưỡng ôn tập. Các hoạt động chưa có điểm chưa được phân loại.</p>@endforelse
</section>
<p class="tdash-learner-note">Tiến độ và điểm thể hiện dữ liệu hiện có của khóa học. Lượt hỏi dùng khoảng thời gian đang chọn. Điểm quiz là lần hoàn thành gần nhất; bài tập dùng lần nộp mới nhất và chỉ hiển thị điểm khi đã chấm. Điểm hoàn thành nội dung và video không dùng để xác định hoạt động cần ôn.</p>
<div class="tdash-learner-lessons">
    @forelse($learnerSupport['lessons'] as $row)
        <details class="tdash-learner-lesson" @if($row['activities']->where('needs_review', true)->isNotEmpty() || $row['activities']->where('pending', true)->isNotEmpty()) open @endif>
            <summary><span>{{ $row['lesson']->title }} @if(!$row['lesson']->is_visible)<small>· Bài học đang ẩn</small>@endif</span><span class="tdash-lesson-summary">{{ $row['completed'] ? 'Đã hoàn thành' : 'Chưa hoàn thành' }} · {{ $row['completed_activities'] }}/{{ $row['total_activities'] }} hoạt động · {{ $row['questions'] }} lượt hỏi</span></summary>
            <div class="tdash-table-wrap"><table><thead><tr><th>Hoạt động</th><th>Kết quả đánh giá</th><th>Ghi nhận lúc</th><th>Trạng thái</th><th>Hành động</th></tr></thead><tbody>
                @forelse($row['activities'] as $item)<tr><td>{{ $item['activity']->title }} @if(!$item['activity']->is_visible)<small>· Đang ẩn</small>@endif</td><td>{{ $item['score'] === null ? ($item['pending'] ? 'Chờ chấm' : 'Chưa có điểm đánh giá') : $item['score'].'%' }} @if($item['source'])<small class="tdash-grade-source">{{ $item['source'] }}</small>@endif</td><td>{{ $item['assessed_at']?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '—' }}</td><td>{{ $item['completed'] ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}</td><td><a class="tdash-outline" href="{{ route('activities.show', $item['activity']->id) }}">Xem hoạt động</a></td></tr>@empty<tr><td colspan="5">Bài học chưa có hoạt động.</td></tr>@endforelse
            </tbody></table></div>
        </details>
    @empty<p class="tdash-empty">Khóa học chưa có bài học.</p>@endforelse
</div>
