@extends('ai-tutor::layouts.tutor')
@section('title', 'Lịch sử đánh giá kỹ năng')
@section('content')
<main class="tai-license" style="max-width:1000px;padding:24px">
    <a href="{{ route('dashboard.v2') }}">← Dashboard v2</a>
    <h1>Lịch sử đánh giá kỹ năng</h1>
    <p>Điểm luyện tập AI giúp theo dõi tiến bộ; các thang điểm được hiển thị riêng.</p>
    @forelse($history ?? [] as $snapshot)
        <section>
            <h2>{{ ucfirst($snapshot->skill) }} · {{ $snapshot->score + 0 }}{{ $snapshot->score_scale === 'ielts_band_0_9' ? '/9 — IELTS Writing' : '/100' }}</h2>
            <p>{{ $snapshot->assessed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }} · {{ $snapshot->source === 'speech_audio' ? 'Luyện phát âm' : 'AI Tutor Writing' }}</p>
            @if(!empty($snapshot->issues))<p>{{ count($snapshot->issues) }} lỗi cần luyện thêm.</p>@endif
        </section>
    @empty
        <section>Chưa có lịch sử đánh giá. Hoàn thành một bài Writing hoặc bài chấm âm thanh để bắt đầu.</section>
    @endforelse
    @if($history){{ $history->links() }}@endif
</main>
@endsection
