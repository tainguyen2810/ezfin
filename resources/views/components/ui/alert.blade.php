@props(['variant' => 'info', 'title' => null])
@php
    $variants = [
        'info' => ['bg-primary-50 border-primary-200 text-navy-900', 'information-circle', 'text-primary-600'],
        'success' => ['bg-income-soft border-green-200 text-navy-900', 'check-circle', 'text-income'],
        'warning' => ['bg-warning-soft border-amber-200 text-navy-900', 'exclamation-triangle', 'text-amber-600'],
        'danger' => ['bg-expense-soft border-red-200 text-navy-900', 'exclamation-circle', 'text-expense'],
    ];
    [$box, $icon, $iconColor] = $variants[$variant];
@endphp
<div {{ $attributes->merge(['class' => "flex gap-3 rounded-lg border p-3 text-sm $box"]) }} role="{{ in_array($variant, ['warning', 'danger']) ? 'alert' : 'status' }}">
    <x-ui.icon :name="$icon" class="size-5 shrink-0 {{ $iconColor }}" />
    <div>
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="text-slate-700">{{ $slot }}</div>
    </div>
</div>
