{{-- Biểu đồ tròn rỗng (phân bổ chi tiêu) dựng bằng SVG, hiện cả khi tắt JS. Chú thích bên cạnh là bảng số liệu:
     nhãn, số tiền, tỷ lệ, nên màu không phải cách duy nhất để phân biệt.
     segments: [['label' => 'Ăn uống', 'value' => số đồng, 'color' => '#2a78d6'], ...] (đã sắp xếp, tối đa ~7 phần + "Khác");
     total: số ở giữa; centerLabel: chữ nhỏ dưới số; caption: một câu mô tả cho trình đọc màn hình. --}}
@props(['segments', 'total', 'centerLabel' => null, 'caption'])
@php
    $sum = max(1, array_sum(array_column($segments, 'value')));
    $gap = count($segments) > 1 ? 0.8 : 0; // khe 2px giữa các phần (đơn vị: % chu vi)
    $offset = 25; // bắt đầu từ đỉnh, đi theo chiều kim đồng hồ
    $percent = fn ($value) => (int) round($value / $sum * 100);
@endphp
{{-- Xếp ngang khi khung đủ rộng (container query), không phụ thuộc bề rộng màn hình. --}}
<div {{ $attributes->merge(['class' => '@container']) }}>
<figure class="flex flex-col items-center gap-5 @md:flex-row">
    <figcaption class="sr-only">{{ $caption }}</figcaption>
    <div class="relative size-40 shrink-0 @md:size-44">
        <svg viewBox="0 0 42 42" class="size-full" aria-hidden="true">
            <circle cx="21" cy="21" r="15.915" fill="none" stroke="#f1f5f9" stroke-width="5" />
            @foreach ($segments as $segment)
                @php($length = $segment['value'] / $sum * 100)
                <circle cx="21" cy="21" r="15.915" fill="none" stroke="{{ $segment['color'] }}" stroke-width="5"
                    stroke-dasharray="{{ max(0, $length - $gap) }} {{ 100 - max(0, $length - $gap) }}" stroke-dashoffset="{{ $offset }}">
                    <title>{{ $segment['label'] }}: {{ Format::money($segment['value']) }} ({{ $percent($segment['value']) }}%)</title>
                </circle>
                @php($offset -= $length)
            @endforeach
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
            <p class="num text-base font-bold text-navy-900">{{ Format::compactMoney($total) }}</p>
            @if ($centerLabel)<p class="text-xs text-slate-500">{{ $centerLabel }}</p>@endif
        </div>
    </div>
    <table class="w-full text-sm">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead class="sr-only"><tr><th>Mục</th><th>Số tiền</th><th>Tỷ lệ</th></tr></thead>
        <tbody>
            @foreach ($segments as $segment)
                <tr>
                    <td class="py-1 pr-2"><span class="flex items-center gap-2 text-slate-700"><span class="size-2.5 shrink-0 rounded-full" style="background: {{ $segment['color'] }}"></span><span class="truncate">{{ $segment['label'] }}</span></span></td>
                    <td class="num py-1 pr-2 text-right whitespace-nowrap text-slate-500">{{ Format::compactMoney($segment['value']) }}</td>
                    <td class="num w-10 py-1 text-right font-semibold text-navy-900">{{ $percent($segment['value']) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</figure>
</div>
