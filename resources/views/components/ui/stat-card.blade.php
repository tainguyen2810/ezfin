{{-- Thẻ số liệu: nhãn, giá trị, biến động so với kỳ trước. tone: income | expense | neutral | primary.
     compactValue: giá trị rút gọn hiển thị trên màn hình hẹp (ví dụ "28,5 tr"). --}}
@props(['label', 'value', 'compactValue' => null, 'change' => null, 'trend' => null, 'icon' => null, 'tone' => 'primary'])
@php
    $tones = [
        'primary' => 'bg-primary-50 text-primary-600',
        'income' => 'bg-income-soft text-income',
        'expense' => 'bg-expense-soft text-expense',
        'neutral' => 'bg-slate-100 text-slate-600',
    ];
    $trendClass = match ($trend) {
        'up' => 'text-income',
        'down' => 'text-expense',
        default => 'text-slate-500',
    };
@endphp
<div {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white p-4 shadow-card']) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-slate-500">{{ $label }}</p>
        @if ($icon)
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tones[$tone] }}"><x-ui.icon :name="$icon" class="size-5" /></span>
        @endif
    </div>
    <p class="num mt-1 text-lg font-bold whitespace-nowrap text-navy-900 sm:text-xl xl:text-2xl" title="{{ $value }}">
        @if ($compactValue)
            <span class="sm:hidden">{{ $compactValue }}</span><span class="hidden sm:inline">{{ $value }}</span>
        @else
            {{ $value }}
        @endif
    </p>
    @if ($change)
        <p class="mt-1 flex items-center gap-1 text-xs font-medium {{ $trendClass }}">
            @if ($trend === 'up')<x-ui.icon name="arrow-trending-up" class="size-4 shrink-0" />@elseif ($trend === 'down')<x-ui.icon name="arrow-trending-down" class="size-4 shrink-0" />@endif
            {{ $change }}
        </p>
    @endif
</div>
