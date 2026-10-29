{{-- Nguồn và thời điểm xác minh dữ liệu. Quá hạn rà soát → cảnh báo "cần xác minh lại".
     stale: truyền sẵn khi dữ liệu có hạn kiểm tra riêng (ví dụ next_review_at của sản phẩm). --}}
@props(['verifiedAt', 'source', 'sourceUrl' => null, 'staleAfterDays' => 30, 'stale' => null])
@php
    $verified = \Illuminate\Support\Carbon::parse($verifiedAt);
    $stale ??= $verified->lt(now()->subDays($staleAfterDays));
@endphp
<p {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-2 gap-y-1 text-xs '.($stale ? 'text-amber-700' : 'text-slate-500')]) }}>
    <x-ui.icon :name="$stale ? 'exclamation-triangle' : 'clock'" class="size-3.5" />
    <span>Cập nhật {{ Format::date($verified) }}</span>
    <span aria-hidden="true">·</span>
    <span>Nguồn:
        @if ($sourceUrl)
            <a href="{{ $sourceUrl }}" class="underline hover:text-primary-700" target="_blank" rel="noopener nofollow">{{ $source }}</a>
        @else
            {{ $source }}
        @endif
    </span>
    @if ($stale)<x-ui.badge variant="warning">Cần xác minh lại</x-ui.badge>@endif
</p>
