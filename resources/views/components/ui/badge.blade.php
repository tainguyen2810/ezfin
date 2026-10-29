@props(['variant' => 'neutral', 'icon' => null])
@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'primary' => 'bg-primary-50 text-primary-700',
        'success' => 'bg-income-soft text-green-700',
        'warning' => 'bg-warning-soft text-amber-700',
        'danger' => 'bg-expense-soft text-red-700',
        'sponsored' => 'bg-amber-100 text-amber-800',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium '.$variants[$variant]]) }}>
    @if ($icon)<x-ui.icon :name="$icon" class="size-3.5" />@endif
    {{ $slot }}
</span>
