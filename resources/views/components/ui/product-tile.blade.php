{{-- Ô sản phẩm dạng lưới (3 ô/hàng trên desktop). Bấm ô mở hộp thông tin nhanh; tắt JS thì đi tới trang chi tiết.
     keyFacts: 2-4 thông số chính hiện trên ô; facts: toàn bộ thông số trong hộp. note: ghi chú (vd. điều kiện phí), chỉ hiện trong hộp. --}}
@props([
    'id', 'name', 'institution', 'logo' => null, 'image' => null, 'keyFacts' => [], 'facts' => [], 'highlights' => [], 'note' => null,
    'detailUrl' => null, 'partnerUrl' => null, 'partnerLabel' => 'Đăng ký', 'sponsored' => false, 'comparable' => true,
])
@php $modal = 'tile-'.$id; @endphp
<article {{ $attributes->merge(['class' => 'group relative flex flex-col rounded-card border border-slate-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-lg']) }}>
    <a href="{{ $detailUrl ?? '#' }}" x-data x-on:click.prevent="$dispatch('open-modal', @js($modal))" class="flex flex-1 flex-col p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 rounded-card">
        <div class="flex h-36 items-center justify-center rounded-xl bg-gradient-to-br from-slate-50 to-primary-50">
            @if ($image)
                <img src="{{ $image }}" alt="{{ $name }}" width="176" height="111" class="h-28 w-44 rounded-lg object-cover shadow-md transition group-hover:scale-105" loading="lazy">
            @elseif ($logo)
                <img src="{{ $logo }}" alt="{{ $institution }}" width="96" height="48" class="max-h-12 max-w-24 object-contain" loading="lazy">
            @endif
        </div>
        <div class="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
            @if ($logo)<img src="{{ $logo }}" alt="" width="16" height="16" class="size-4 object-contain">@endif
            {{ $institution }}
            @if ($sponsored)<x-ui.badge variant="sponsored" class="ml-auto">Tài trợ</x-ui.badge>@endif
        </div>
        <h3 class="mt-1 line-clamp-2 text-base font-semibold leading-snug text-navy-900 group-hover:text-primary-700">{{ $name }}</h3>
        @if ($highlights)
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach (array_slice($highlights, 0, 3) as $highlight)<x-ui.badge variant="primary">{{ $highlight }}</x-ui.badge>@endforeach
            </div>
        @endif
        @if ($keyFacts)
            <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100 pt-3">
                @foreach ($keyFacts as $label => $value)
                    <div>
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="num text-sm font-semibold text-navy-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </a>
    <div class="flex items-center gap-2 border-t border-slate-100 px-4 py-3">
        @if ($comparable)
            <label x-data class="mr-auto flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" class="size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-600"
                    x-bind:checked="$store.compare.has(@js($id))"
                    x-on:change="$store.compare.toggle({ id: @js($id), name: @js($name) }); $el.checked = $store.compare.has(@js($id))">
                So sánh
            </label>
        @endif
        <button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:text-primary-800">
            Xem nhanh <x-ui.icon name="chevron-right" class="size-4" />
        </button>
    </div>
</article>

<x-ui.modal :name="$modal" :title="$name" max-width="max-w-lg">
    <div class="flex items-center gap-4">
        @if ($image)<img src="{{ $image }}" alt="" width="128" height="81" class="h-20 w-32 shrink-0 rounded-lg object-cover shadow-md">@endif
        <div class="min-w-0">
            <p class="text-sm text-slate-500">{{ $institution }}</p>
            @if ($highlights)
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach ($highlights as $highlight)<x-ui.badge variant="primary">{{ $highlight }}</x-ui.badge>@endforeach
                </div>
            @endif
        </div>
    </div>
    @if ($facts)
        <dl class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3">
            @foreach ($facts as $label => $value)
                <div>
                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                    <dd class="num text-sm font-semibold text-navy-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    @if ($note)<p class="mt-3 text-sm text-slate-600"><span class="font-medium text-navy-900">Phí:</span> {{ Str::limit($note, 180) }}</p>@endif
    @isset($footer)<div class="mt-3">{{ $footer }}</div>@endisset
    <x-slot:footer>
        @if ($detailUrl)<x-ui.button :href="$detailUrl" :variant="$partnerUrl ? 'outline' : 'primary'">Xem trang chi tiết</x-ui.button>@endif
        @if ($partnerUrl)<x-ui.button :href="$partnerUrl" icon="arrow-top-right-on-square" rel="sponsored nofollow noopener" target="_blank">{{ $partnerLabel }}</x-ui.button>@endif
    </x-slot:footer>
</x-ui.modal>
