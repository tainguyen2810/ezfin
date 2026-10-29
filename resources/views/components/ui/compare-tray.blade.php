{{-- Khay so sánh dính đáy màn hình; hiện khi đã chọn ít nhất 1 sản phẩm. --}}
@props(['compareUrl'])
<div x-data x-show="$store.compare.items.length > 0" x-cloak x-transition
    class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-4px_12px_rgb(15_23_42/0.08)] backdrop-blur lg:bottom-4 lg:left-1/2 lg:right-auto lg:w-[720px] lg:-translate-x-1/2 lg:rounded-xl lg:border">
    <div class="flex items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-navy-900">
                Đã chọn <span x-text="$store.compare.items.length"></span>/<span x-text="$store.compare.max"></span>
            </p>
            <p class="truncate text-xs text-slate-500" x-text="$store.compare.items.map(i => i.name).join(' · ')"></p>
        </div>
        <x-ui.button variant="ghost" size="sm" x-on:click="$store.compare.clear()">Bỏ chọn</x-ui.button>
        <a x-bind:href="@js($compareUrl) + '?ids=' + $store.compare.items.map(i => i.id).join(',')"
            x-bind:aria-disabled="$store.compare.items.length < 2"
            class="inline-flex h-10 items-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700 aria-disabled:pointer-events-none aria-disabled:opacity-50">
            So sánh
        </a>
    </div>
</div>
