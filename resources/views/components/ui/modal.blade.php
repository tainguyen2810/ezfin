{{-- Mở: $dispatch('open-modal', 'ten-modal'). Đóng: Esc, bấm nền, hoặc $dispatch('close-modal'). --}}
@props(['name', 'title', 'maxWidth' => 'max-w-md'])
<div x-data="{ open: false }"
    x-on:open-modal.window="open = ($event.detail === @js($name))"
    x-on:close-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open" x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title">
    <div class="absolute inset-0 bg-navy-950/50" x-on:click="open = false" x-show="open" x-transition.opacity></div>
    <div class="relative w-full {{ $maxWidth }} rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl"
        x-show="open" x-transition x-trap.noscroll="open">
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $name }}-title" class="text-lg font-semibold">{{ $title }}</h2>
            <button type="button" class="rounded-md p-1 text-slate-500 hover:bg-slate-100" x-on:click="open = false">
                <x-ui.icon name="x-mark" class="size-5" /><span class="sr-only">Đóng</span>
            </button>
        </div>
        <div class="mt-3 text-sm text-slate-600">{{ $slot }}</div>
        @isset($footer)
            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">{{ $footer }}</div>
        @endisset
    </div>
</div>
