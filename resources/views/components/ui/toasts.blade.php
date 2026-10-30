{{-- Vùng hiển thị toast. Tự nhận flash session('toast') = ['message' => ..., 'type' => success|warning|danger]. --}}
<div x-data="toasts(@js(session()->has('toast') ? [session('toast')] : []))"
    x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
    aria-live="polite">
    <template x-for="item in items" :key="item.id">
        <div class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl border bg-white p-3 text-sm shadow-lg"
            :class="{ 'border-green-200': item.type === 'success', 'border-amber-200': item.type === 'warning', 'border-red-200': item.type === 'danger' }"
            x-transition>
            <span class="mt-0.5 size-2 shrink-0 rounded-full" :class="{ 'bg-income': item.type === 'success', 'bg-warning': item.type === 'warning', 'bg-expense': item.type === 'danger' }"></span>
            <p class="flex-1 text-navy-900" x-text="item.message"></p>
            <button type="button" class="text-slate-400 hover:text-slate-600" x-on:click="remove(item.id)"><span class="sr-only">Đóng</span>&times;</button>
        </div>
    </template>
</div>
