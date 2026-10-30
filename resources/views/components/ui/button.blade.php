@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'loading' => false,
    'icon' => null,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50';
    $sizes = [
        'sm' => 'h-8 px-3 text-sm',
        'md' => 'h-10 px-4 text-sm',
        'lg' => 'h-12 px-6 text-base',
    ];
    $variants = [
        'primary' => 'bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-primary-600',
        'accent' => 'bg-highlight text-navy-900 hover:bg-highlight-strong focus-visible:outline-primary-600',
        'secondary' => 'bg-primary-100 text-primary-700 hover:bg-primary-200 focus-visible:outline-primary-600',
        'outline' => 'border border-slate-300 bg-white text-navy-900 hover:border-navy-900 focus-visible:outline-primary-600',
        'ghost' => 'text-navy-900 hover:bg-slate-100 focus-visible:outline-primary-600',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600',
    ];
    $classes = $base.' '.$sizes[$size].' '.$variants[$variant];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if ($loading) aria-disabled="true" aria-busy="true" @endif>
        @if ($icon) <x-ui.icon :name="$icon" class="size-4" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($loading) @if ($loading) aria-busy="true" @endif>
        @if ($loading)
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        @elseif ($icon)
            <x-ui.icon :name="$icon" class="size-4" />
        @endif
        {{ $slot }}
    </button>
@endif
