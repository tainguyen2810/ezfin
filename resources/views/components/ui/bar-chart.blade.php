{{-- Biểu đồ cột (có thể chồng nhiều phần) dựng bằng HTML/CSS: hiển thị cả khi tắt JS; rê chuột hoặc Tab vào cột để xem số.
     labels: nhãn trục ngang; series: [['label' => 'Tiền gốc', 'tone' => 'primary|income|cost', 'values' => [số đồng, ...]], ...]
     (phần đầu nằm dưới cùng); caption: một câu mô tả cho trình đọc màn hình; xTitle: tên trục ngang (Năm, Tháng).
     Biểu đồ luôn đi kèm bảng số liệu đầy đủ ở trang dùng nó. --}}
@props(['labels', 'series', 'caption', 'xTitle' => null, 'labelPrefix' => ''])
@php
    $tones = ['primary' => 'bg-primary-600', 'income' => 'bg-income', 'cost' => 'bg-orange-600'];
    $labels = array_values($labels);
    $count = count($labels);
    $totals = array_map(fn ($i) => array_sum(array_map(fn ($s) => $s['values'][$i], $series)), range(0, $count - 1));

    // Trục dọc: 4 mốc tròn (1, 2, 2,5, 5 × 10^n).
    $max = max(1, ...$totals);
    $magnitude = 10 ** floor(log10($max / 4));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $magnitude)->first(fn ($s) => $s * 4 >= $max);
    $top = $step * ceil($max / $step);
    $ticks = range(0, $top, $step);

    // Nhãn trục dọc ngắn: 1,5 tỷ, 75 tr, 500 N.
    $axisLabel = fn ($value) => match (true) {
        $value == 0 => '0',
        $value >= 1_000_000 => Format::compactMoney($value),
        default => Format::decimal($value / 1_000, 1).' N',
    };

    // Nhãn trục ngang thưa dần để không chồng chữ (tối đa ~8 nhãn).
    $every = max(1, (int) ceil($count / 8));
    $shown = range(0, $count - 1, $every);
    if (end($shown) !== $count - 1) {
        // Luôn hiện nhãn cuối; bỏ nhãn liền trước nếu quá sát.
        if ($count - 1 - end($shown) < $every) {
            array_pop($shown);
        }
        $shown[] = $count - 1;
    }
@endphp
<figure {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white p-4 shadow-card']) }}>
    <figcaption class="sr-only">{{ $caption }}</figcaption>
    @if (count($series) > 1)
        <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600" aria-hidden="true">
            @foreach ($series as $item)
                <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm {{ $tones[$item['tone']] }}"></span>{{ $item['label'] }}</li>
            @endforeach
        </ul>
    @endif

    <div class="relative mt-4 h-56 pl-14 sm:h-64">
        @foreach ($ticks as $tick)
            <div class="absolute right-0 left-14 border-t border-slate-100" style="bottom: {{ $tick / $top * 100 }}%" aria-hidden="true">
                <span class="num absolute -left-14 w-12 -translate-y-1/2 text-right text-[11px] leading-none text-slate-500">{{ $axisLabel($tick) }}</span>
            </div>
        @endforeach

        <div class="relative flex h-full items-end gap-0.5">
            @foreach ($labels as $i => $label)
                @php
                    $nonZero = array_keys(array_filter($series, fn ($s) => $s['values'][$i] > 0));
                    $topSeries = end($nonZero);
                    $readout = $labelPrefix.$label.': '.collect($series)->map(fn ($s) => $s['label'].' '.Format::money($s['values'][$i]))->implode(', ');
                @endphp
                <div class="group flex h-full min-w-0 flex-1 items-end justify-center outline-none" tabindex="0" aria-label="{{ $readout }}">
                    <div class="flex w-full max-w-6 flex-col-reverse gap-0.5 transition group-hover:opacity-80 group-focus-visible:opacity-80" style="height: {{ $totals[$i] / $top * 100 }}%">
                        @foreach ($series as $index => $item)
                            @if ($item['values'][$i] > 0)
                                <div class="{{ $tones[$item['tone']] }} {{ $index === $topSeries ? 'rounded-t' : '' }}" style="flex: {{ $item['values'][$i] }} 1 0"></div>
                            @endif
                        @endforeach
                    </div>
                    {{-- Chú thích bám theo khung biểu đồ, nằm phía đối diện cột đang xem để không che và không tràn ra ngoài. --}}
                    <div class="pointer-events-none absolute top-0 z-10 hidden min-w-40 rounded-lg border border-slate-200 bg-white p-2 text-xs shadow-lg group-hover:block group-focus-visible:block {{ $i < $count / 2 ? 'right-0' : 'left-0' }}" aria-hidden="true">
                        <p class="font-medium text-slate-500">{{ $labelPrefix }}{{ $label }}</p>
                        @foreach (array_reverse($series) as $item)
                            <p class="mt-1 flex items-center gap-2 whitespace-nowrap">
                                <span class="h-0.5 w-3 shrink-0 rounded {{ $tones[$item['tone']] }}"></span>
                                <span class="num font-semibold text-navy-900">{{ Format::money($item['values'][$i]) }}</span>
                                <span class="text-slate-500">{{ $item['label'] }}</span>
                            </p>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-1.5 flex gap-0.5 pl-14 text-[11px] text-slate-500" aria-hidden="true">
        @foreach ($labels as $i => $label)
            <span class="num min-w-0 flex-1 text-center">{{ in_array($i, $shown, true) ? $label : '' }}</span>
        @endforeach
    </div>
    @if ($xTitle)
        <p class="mt-1 pl-14 text-center text-[11px] text-slate-500" aria-hidden="true">{{ $xTitle }}</p>
    @endif
</figure>
