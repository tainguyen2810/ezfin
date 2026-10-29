{{-- Thẻ sản phẩm trong danh sách so sánh. facts: ['Phí thường niên' => '0 đ', ...]; highlights: nhãn khách quan.
     detailUrl null = chưa có trang chi tiết (không hiện nút). Slot footer: nguồn, ngày xác minh. --}}
@props([
    'id', 'name', 'institution', 'logo' => null, 'facts' => [], 'highlights' => [],
    'detailUrl' => null, 'partnerUrl' => null, 'partnerLabel' => 'Đăng ký', 'sponsored' => false, 'comparable' => true,
])
<article {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white p-4 shadow-card sm:p-5']) }}>
    <div class="flex gap-4">
        <div class="flex h-14 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $institution }}" class="max-h-10 max-w-16 object-contain" loading="lazy">
            @else
                <span class="text-xs font-semibold text-slate-500">{{ Str::limit($institution, 10, '') }}</span>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-base font-semibold">
                    @if ($detailUrl)<a href="{{ $detailUrl }}" class="hover:text-primary-700">{{ $name }}</a>@else{{ $name }}@endif
                </h3>
                @if ($sponsored)<x-ui.badge variant="sponsored">Tài trợ</x-ui.badge>@endif
            </div>
            <p class="text-sm text-slate-500">{{ $institution }}</p>
            @if ($highlights)
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($highlights as $highlight)<x-ui.badge variant="primary">{{ $highlight }}</x-ui.badge>@endforeach
                </div>
            @endif
        </div>
    </div>
    @if ($facts)
        {{-- 4 thông số: 4 cột để không lẻ một ô ở dòng dưới. --}}
        <dl @class(['mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4', count($facts) === 4 ? 'sm:grid-cols-4' : 'sm:grid-cols-3'])>
            @foreach ($facts as $label => $value)
                <div>
                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                    <dd class="num mt-0.5 text-sm font-semibold text-navy-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    @isset($footer)
        <div class="mt-4 border-t border-slate-100 pt-3">{{ $footer }}</div>
    @endisset
    @if ($comparable || $detailUrl || $partnerUrl)
    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        @if ($comparable)
            <label x-data class="flex items-center gap-2 text-sm text-slate-600 sm:mr-auto">
                <input type="checkbox" class="size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-600"
                    x-bind:checked="$store.compare.has(@js($id))"
                    x-on:change="$store.compare.toggle({ id: @js($id), name: @js($name) }); $el.checked = $store.compare.has(@js($id))">
                So sánh
            </label>
        @endif
        @if ($detailUrl)
            <x-ui.button :href="$detailUrl" variant="outline">Xem chi tiết</x-ui.button>
        @endif
        @if ($partnerUrl)
            <x-ui.button :href="$partnerUrl" icon="arrow-top-right-on-square" rel="sponsored nofollow noopener" target="_blank">{{ $partnerLabel }}</x-ui.button>
        @endif
    </div>
    @endif
</article>
