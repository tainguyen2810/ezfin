{{-- Bộ lọc: cột trái trên desktop, ngăn kéo từ dưới lên trên mobile. Form GET để giữ trạng thái lọc trên URL. --}}
@props(['action', 'activeCount' => 0])
<div x-data="{ open: false }">
    <button type="button" class="inline-flex h-10 items-center gap-2 rounded-control border border-slate-300 bg-white px-4 text-sm font-semibold text-navy-900 lg:hidden" x-on:click="open = true">
        <x-ui.icon name="adjustments-horizontal" class="size-4" /> Bộ lọc
        @if ($activeCount)<span class="rounded-full bg-primary-600 px-1.5 text-xs text-white">{{ $activeCount }}</span>@endif
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-navy-950/50 lg:hidden" x-on:click="open = false" x-transition.opacity></div>

    <form method="GET" action="{{ $action }}"
        x-bind:class="open && 'visible! translate-y-0!'"
        x-trap.noscroll="open && window.innerWidth < 1024"
        x-on:keydown.escape="open = false"
        class="invisible fixed inset-x-0 bottom-0 z-50 max-h-[85vh] translate-y-full lg:visible overflow-y-auto rounded-t-2xl bg-white p-5 transition-transform lg:static lg:z-auto lg:max-h-none lg:translate-y-0 lg:rounded-card lg:border lg:border-slate-200 lg:shadow-card">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold">Bộ lọc</h2>
            <button type="button" class="rounded-md p-1 text-slate-500 hover:bg-slate-100 lg:hidden" x-on:click="open = false"><x-ui.icon name="x-mark" /><span class="sr-only">Đóng bộ lọc</span></button>
        </div>
        <div class="space-y-4">{{ $slot }}</div>
        <div class="mt-5 flex gap-2">
            <x-ui.button :href="$action" variant="outline" class="flex-1">Xóa lọc</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Áp dụng</x-ui.button>
        </div>
    </form>
</div>
