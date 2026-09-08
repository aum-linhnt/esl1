@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white">📊 Sổ điểm học tập</h2>
            <p class="text-sm text-gray-400 mt-1">Tổng hợp điểm số của bạn qua các khóa học</p>
        </div>
    </div>

    {{-- Overall GPA Card --}}
    <div class="card-dark p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">Điểm Trung Bình Tổng (Overall Average)</p>
                <div class="flex items-baseline gap-3">
                    <span class="text-4xl font-bold text-white">{{ number_format($overallGPA, 1) }}</span>
                    <span class="text-lg font-semibold px-3 py-1 rounded-lg bg-{{ $overallFormatted['badge_color'] }}-500/20 text-{{ $overallFormatted['badge_color'] }}-400">
                        {{ $overallFormatted['grade_letter'] }}
                    </span>
                    <span class="text-xs text-gray-500 ml-1">{{ $overallFormatted['rank_label'] }}</span>
                </div>
            </div>
            <div class="w-20 h-20 rounded-full border-4 flex items-center justify-center
                border-{{ $overallFormatted['badge_color'] }}-500">
                <span class="text-xl font-bold text-white">{{ round($overallGPA) }}%</span>
            </div>
        </div>
    </div>

    {{-- Course Grades Table --}}
    @if($courseGrades->isEmpty())
        <div class="card-dark p-12 text-center">
            <p class="text-gray-400">Bạn chưa ghi danh khóa học nào. Hãy ghi danh để bắt đầu!</p>
            <a href="{{ route('courses.index') }}" class="mt-4 inline-block bg-fsel-blue text-white px-6 py-2 rounded-lg hover:bg-fsel-blue/80 transition-colors text-sm">Khám phá khóa học</a>
        </div>
    @else
        <div class="card-dark overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-fsel-border/30">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Khóa học</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Trạng thái</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Thang điểm</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Điểm số</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Xếp loại</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsel-border/20">
                    @foreach($courseGrades as $item)
                        @php $f = $item['formatted']; @endphp
                        <tr class="hover:bg-fsel-navy/50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="bg-fsel-blue/20 text-fsel-blue text-xs font-bold px-2 py-0.5 rounded">{{ $item['course']->level }}</span>
                                    <span class="text-sm font-medium text-white">{{ $item['course']->title }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($item['status'] === 'completed')
                                    <span class="bg-green-500/20 text-green-400 text-xs px-2.5 py-1 rounded-full">Hoàn thành</span>
                                @else
                                    <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2.5 py-1 rounded-full">Đang học</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                    {{ $f['scale_icon'] }} {{ $item['course']->getGradingScaleMeta()['short'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-lg font-bold text-white">{{ $f['score_display'] }}</span>
                                <span class="text-xs text-gray-500">{{ $f['score_suffix'] }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-sm font-bold px-3 py-1 rounded-lg bg-{{ $f['badge_color'] }}-500/20 text-{{ $f['badge_color'] }}-400">
                                        {{ $f['grade_letter'] }}
                                    </span>
                                    <span class="text-[10px] text-gray-500">{{ $f['rank_label'] }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <a href="{{ route('gradebook.show', $item['course']->id) }}" class="text-fsel-blue hover:text-fsel-accent text-sm transition-colors">
                                    Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
