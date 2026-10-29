{{-- Bảng so sánh 2–3 sản phẩm. Mỗi tiêu chí: nhãn chiếm cả dòng, giá trị chia cột bên dưới (đọc được ở 360px).
     products: [['name', 'institution', 'logo' => ?, 'url' => ?, 'removeUrl' => ?]]
     rows: [['label', 'values' => [...], 'best' => [chỉ số cột], 'bestLabel' => 'Thấp nhất']] --}}
@props(['products', 'rows'])
@php
    $columns = ['grid-cols-1', 'grid-cols-2', 'grid-cols-3'][max(0, min(count($products), 3) - 1)];
@endphp
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-card border border-slate-200 bg-white shadow-card']) }}>
    <div class="grid {{ $columns }} divide-x divide-slate-100 border-b border-slate-200">
        @foreach ($products as $product)
            <div class="flex min-w-0 flex-col gap-2 p-3 sm:p-4">
                <div class="flex h-10 w-16 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                    @if ($product['logo'] ?? null)
                        <img src="{{ $product['logo'] }}" alt="{{ $product['institution'] }}" width="48" height="28" class="max-h-7 max-w-12 object-contain" loading="lazy">
                    @else
                        <span class="text-[10px] font-semibold text-slate-500">{{ Str::limit($product['institution'], 8, '') }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold leading-snug break-words text-navy-900">
                        @if ($product['url'] ?? null)<a href="{{ $product['url'] }}" class="hover:text-primary-700">{{ $product['name'] }}</a>@else{{ $product['name'] }}@endif
                    </p>
                    <p class="text-xs text-slate-500">{{ $product['institution'] }}</p>
                </div>
                @if ($product['removeUrl'] ?? null)
                    <a href="{{ $product['removeUrl'] }}" class="mt-auto inline-flex items-center gap-1 text-xs text-slate-500 hover:text-red-700">
                        <x-ui.icon name="x-mark" class="size-3.5" /> Bỏ khỏi so sánh
                    </a>
                @endif
            </div>
        @endforeach
    </div>
    <dl>
        @foreach ($rows as $row)
            <div class="border-b border-slate-100 last:border-b-0">
                <dt class="bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 sm:px-4">{{ $row['label'] }}</dt>
                <div class="grid {{ $columns }} divide-x divide-slate-100">
                    @foreach ($row['values'] as $index => $value)
                        @php $isBest = in_array($index, $row['best'] ?? [], true); @endphp
                        <dd class="num min-w-0 px-3 py-2 text-sm break-words sm:px-4 {{ $isBest ? 'font-semibold text-green-700' : 'text-navy-900' }}">
                            <span class="whitespace-pre-line">{{ $value }}</span>
                            @if ($isBest)
                                <span class="mt-1 block"><x-ui.badge variant="success" icon="check">{{ $row['bestLabel'] }}</x-ui.badge></span>
                            @endif
                        </dd>
                    @endforeach
                </div>
            </div>
        @endforeach
    </dl>
</div>
