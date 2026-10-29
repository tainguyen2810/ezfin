@props(['title', 'description' => null, 'icon' => 'banknotes'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-4 py-10 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-500"><x-ui.icon :name="$icon" class="size-6" /></span>
    <h3 class="mt-3 text-base font-semibold">{{ $title }}</h3>
    @if ($description)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
