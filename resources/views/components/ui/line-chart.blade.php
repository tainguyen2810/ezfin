{{-- Biểu đồ đường cho số liệu theo thời gian (vd. lãi suất %/năm). Đường vẽ bằng SVG, điểm và nhãn bằng HTML
     để không méo chữ; rê chuột hoặc Tab vào điểm để xem số. Luôn đi kèm bảng số liệu ở trang dùng nó.
     labels: nhãn trục ngang; series: [['label' => '12 tháng', 'tone' => 'primary|ink', 'values' => [số|null, ...]], ...];
     format: hàm định dạng giá trị (mặc định Format::percent); caption: mô tả cho trình đọc màn hình. --}}
@props(['labels', 'series', 'caption', 'format' => null, 'framed' => true])
@php
    $tones = [
        'primary' => ['stroke' => 'stroke-primary-500', 'dot' => 'bg-primary-500'],
        'ink' => ['stroke' => 'stroke-navy-900', 'dot' => 'bg-navy-900'],
    ];
    $format ??= fn ($value) => Format::percent($value);
    $labels = array_values($labels);
    $count = count($labels);
    $values = collect($series)->flatMap(fn ($s) => $s['values'])->filter(fn ($v) => $v !== null);
    // Trục dọc bắt đầu gần giá trị nhỏ nhất (lãi suất dao động hẹp), bước 0,5 hoặc 1.
    $step = ($values->max() - $values->min()) > 3 ? 1 : 0.5;
    $bottom = max(0, floor(($values->min() - $step / 2) / $step) * $step);
    $top = ceil(($values->max() + $step / 2) / $step) * $step;
    $ticks = range($bottom, $top, $step);
    $x = fn (int $i) => $count > 1 ? $i / ($count - 1) * 100 : 50;
    $y = fn (float $v) => ($v - $bottom) / max($top - $bottom, 0.0001) * 100;
@endphp
<figure {{ $attributes->class(['rounded-card border border-slate-200 bg-white p-4 shadow-card' => $framed]) }}>
    <figcaption class="sr-only">{{ $caption }}</figcaption>
    @if (count($series) > 1)
        <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600" aria-hidden="true">
            @foreach ($series as $item)
                <li class="flex items-center gap-1.5"><span class="h-0.5 w-4 rounded-full {{ $tones[$item['tone']]['dot'] }}"></span>{{ $item['label'] }}</li>
            @endforeach
        </ul>
    @endif

    <div class="relative mt-4 mr-3 h-52 pl-12 sm:h-60">
        @foreach ($ticks as $tick)
            <div class="absolute right-0 left-12 border-t border-slate-100" style="bottom: {{ $y($tick) }}%" aria-hidden="true">
                <span class="num absolute -left-12 w-10 -translate-y-1/2 text-right text-[11px] leading-none text-slate-500">{{ Format::decimal($tick, 1) }}%</span>
            </div>
        @endforeach

        <div class="absolute inset-y-0 right-0 left-12">
            <svg class="absolute inset-0 size-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                @foreach ($series as $item)
                    @php
                        $points = collect($item['values'])->map(fn ($v, $i) => $v === null ? null : $x($i).','.(100 - $y($v)))->filter()->implode(' ');
                    @endphp
                    <polyline points="{{ $points }}" fill="none" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" class="{{ $tones[$item['tone']]['stroke'] }}" />
                @endforeach
            </svg>
            @foreach ($series as $item)
                @foreach ($item['values'] as $i => $value)
                    @continue($value === null)
                    <span tabindex="0" class="group absolute -translate-x-1/2 translate-y-1/2 rounded-full p-1.5 outline-none" style="left: {{ $x($i) }}%; bottom: {{ $y($value) }}%">
                        <span class="block size-2.5 rounded-full ring-2 ring-white {{ $tones[$item['tone']]['dot'] }} group-hover:scale-125 group-focus:scale-125"></span>
                        <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 rounded-lg bg-navy-900 px-2 py-1 text-xs whitespace-nowrap text-white group-hover:block group-focus:block">
                            {{ $item['label'] }} · {{ $labels[$i] }}: <strong class="num">{{ $format($value) }}</strong>
                        </span>
                    </span>
                @endforeach
            @endforeach
        </div>
    </div>
    <div class="relative mt-2 mr-3 ml-12 h-4 text-[11px] text-slate-500" aria-hidden="true">
        @foreach ($labels as $i => $label)
            @if ($count <= 6 || $i === 0 || $i === $count - 1 || $i % (int) ceil($count / 5) === 0)
                <span @class(['num absolute whitespace-nowrap', '-translate-x-1/2' => $i > 0 && $i < $count - 1, '-translate-x-full' => $i === $count - 1 && $count > 1]) style="left: {{ $x($i) }}%">{{ $label }}</span>
            @endif
        @endforeach
    </div>
</figure>
