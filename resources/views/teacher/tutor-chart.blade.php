@php
    $count = count($daily); $maxQuestions = max(10, (int) ceil(max(array_column($daily, 'questions')) / 10) * 10);
    $step = 800 / max(1, $count); $barWidth = min(18, $step * .55);
@endphp
<div class="tdash-plot" data-tutor-chart>
@foreach($currencies ?: [null] as $currency)
    @php
        $maxCost = max(1, max(array_map(fn ($day) => $day['costs'][$currency] ?? 0, $daily)) * 1.15);
        $points = [];
        foreach ($daily as $i => $day) if (isset($day['costs'][$currency])) $points[] = (55 + ($i + .5) * $step).','.round(195 - 150 * $day['costs'][$currency] / $maxCost, 2);
    @endphp
    <svg preserveAspectRatio="none" viewBox="0 0 925 245" role="img" aria-label="Lượt hỏi và chi phí {{ $currency ?? 'chưa có giá' }} theo ngày" data-currency="{{ $currency }}" @if($currency !== $selectedCurrency) hidden @endif>
        <defs><linearGradient id="tutor-bars-{{ $currency ?? 'none' }}" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#aaa1ff"/><stop offset="1" stop-color="#7766ff"/></linearGradient></defs>
        <text x="12" y="22">Lượt hỏi</text><text x="917" y="22" text-anchor="end">Chi phí {{ $currency ? '('.$currency.')' : '(chưa có giá)' }}</text>
        @for($tick = 0; $tick <= 5; $tick++)
            @php($y = 195 - 30 * $tick)
            <line x1="55" x2="855" y1="{{ $y }}" y2="{{ $y }}" class="tdash-gridline"/>
            <text x="40" y="{{ $y + 4 }}" text-anchor="end">{{ round($maxQuestions * $tick / 5) }}</text>
            @if($currency)<text x="870" y="{{ $y + 4 }}">{{ number_format($maxCost * $tick / 5, $maxCost < 10 ? 2 : 0) }}</text>@endif
        @endfor
        @foreach($daily as $i => $day)
            @php($x = 55 + ($i + .5) * $step)
            <line x1="{{ $x }}" x2="{{ $x }}" y1="45" y2="195" class="tdash-gridline"/>
            <rect x="{{ $x - $barWidth/2 }}" y="{{ 195 - 150 * $day['questions'] / $maxQuestions }}" width="{{ $barWidth }}" height="{{ 150 * $day['questions'] / $maxQuestions }}" rx="1" fill="url(#tutor-bars-{{ $currency ?? 'none' }})"><title>{{ $day['label'] }}: {{ $day['questions'] }} lượt hỏi</title></rect>
            @if($i % max(1, (int) ceil($count / 15)) === 0)<text x="{{ $x }}" y="225" text-anchor="middle">{{ $day['label'] }}</text>@endif
        @endforeach
        @if($points)<polyline points="{{ implode(' ', $points) }}" fill="none" stroke="#00aa98" stroke-width="2.5"/>@endif
        @foreach($daily as $i => $day)
            @if(isset($day['costs'][$currency]))<circle cx="{{ 55 + ($i + .5) * $step }}" cy="{{ 195 - 150 * $day['costs'][$currency] / $maxCost }}" r="4.5" fill="#00aa98" stroke="white" stroke-width="1.5"><title>{{ $day['label'] }}: {{ number_format($day['costs'][$currency], 6, '.', ',') }} {{ $currency }} đã có giá; {{ $day['unpriced'] }} lượt chưa có giá</title></circle>@endif
        @endforeach
        @if(array_sum(array_column($daily, 'questions')) === 0)<text x="450" y="115" text-anchor="middle">Chưa có lượt hỏi trong kỳ</text>@endif
    </svg>
@endforeach
</div>
