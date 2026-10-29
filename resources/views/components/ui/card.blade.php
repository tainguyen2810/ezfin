@props(['title' => null, 'padding' => true])
<section {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white shadow-card']) }}>
    @if ($title || isset($action))
        <header class="flex items-center justify-between gap-4 px-4 pt-4 sm:px-5 sm:pt-5">
            @if ($title)<h2 class="text-base font-semibold">{{ $title }}</h2>@endif
            @isset($action)<div class="text-sm">{{ $action }}</div>@endisset
        </header>
    @endif
    <div @class(['p-4 sm:p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
