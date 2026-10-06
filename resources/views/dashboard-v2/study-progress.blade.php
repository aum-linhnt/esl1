@extends('ai-tutor::layouts.tutor')
@section('title', 'Thời gian học trong 7 ngày')
@section('content')
<main class="tai-license" style="max-width:1000px;padding:24px">
    <a href="{{ route('dashboard.v2') }}">← Dashboard v2</a>
    <h1>Thời gian học trong 7 ngày</h1>
    <p>Các khoảng thời gian chồng nhau được tính một lần. Bài học bao gồm hoạt động trong khóa học.</p>
    <section style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;text-align:left">
        <thead><tr><th>Ngày</th><th>Bài học</th><th>Speaking</th><th>Writing</th><th>Tổng</th></tr></thead>
        <tbody>@foreach($studyDays as $day)<tr>
            <td style="padding:12px 0">{{ $day['date'] }}</td>
            @foreach(['lesson', 'speaking', 'writing'] as $source)<td>{{ intdiv($day['sources'][$source], 60) }}p {{ $day['sources'][$source] % 60 }}s</td>@endforeach
            <td><strong>{{ intdiv($day['seconds'], 60) }}p {{ $day['seconds'] % 60 }}s</strong></td>
        </tr>@endforeach</tbody>
    </table></section>
    <p>Thời gian mới được ghi khi bạn tương tác với trang; trang ẩn hoặc không hoạt động quá 2 phút sẽ tạm dừng. Nhật ký bài học cũ được giữ lại theo dữ liệu đã ghi.</p>
</main>
@endsection
