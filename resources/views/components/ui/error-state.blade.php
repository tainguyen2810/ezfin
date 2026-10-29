@props(['title' => 'Không tải được dữ liệu', 'description' => 'Vui lòng thử lại sau ít phút.'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-4 py-10 text-center']) }} role="alert">
    <span class="flex size-12 items-center justify-center rounded-full bg-expense-soft text-expense"><x-ui.icon name="exclamation-triangle" class="size-6" /></span>
    <h3 class="mt-3 text-base font-semibold">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @if (! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
