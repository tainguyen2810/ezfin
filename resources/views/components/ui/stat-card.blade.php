{{-- Thẻ số liệu: nhãn, giá trị, biến động so với kỳ trước. tone: income | expense | neutral | primary.
     compactValue: giá trị rút gọn hiển thị trên màn hình hẹp (ví dụ "28,5 tr").
     trend (up/down) là chiều mũi tên; positive (true/false) đặt màu tốt/xấu khi khác chiều, ví dụ chi tiêu tăng là xấu.
     dark: thẻ nền tối nổi bật, dùng cho số liệu chính của màn hình (một thẻ mỗi màn). --}}
@props(['label', 'value', 'compactValue' => null, 'change' => null, 'trend' => null, 'icon' => null, 'tone' => 'primary', 'positive' => null, 'dark' => false])
@php
    $tones = [
        'primary' => 'bg-primary-50 text-primary-600',
        'income' => 'bg-income-soft text-income',
        'expense' => 'bg-expense-soft text-expense',
        'neutral' => 'bg-slate-100 text-slate-600',
    ];
    $trendClass = $positive !== null ? ($positive ? 'text-income' : 'text-expense') : match ($trend) {
        'up' => 'text-income',
        'down' => 'text-expense',
        default => 'text-slate-500',
    };
    if ($dark) {
        $tones[$tone] = 'bg-white/10 text-highlight';
        $trendClass = 'text-highlight';
    }
@endphp
<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-card border p-4 shadow-card '.($dark ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-200 bg-white')]) }}>
    @if ($dark)
        <span aria-hidden="true" class="pointer-events-none absolute -right-8 -top-10 size-28 rounded-full bg-primary-500/50"></span>
    @endif
    <div class="relative flex items-start justify-between gap-2">
        <p @class(['text-sm', 'text-white/70' => $dark, 'text-slate-500' => ! $dark])>{{ $label }}</p>
        @if ($icon)
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] }}"><x-ui.icon :name="$icon" class="size-5" /></span>
        @endif
    </div>
    <p @class(['num relative mt-1 text-lg font-bold tracking-tight whitespace-nowrap sm:text-xl xl:text-2xl', 'text-white' => $dark, 'text-navy-900' => ! $dark]) title="{{ $value }}">
        @if ($compactValue)
            <span class="sm:hidden">{{ $compactValue }}</span><span class="hidden sm:inline">{{ $value }}</span>
        @else
            {{ $value }}
        @endif
    </p>
    @if ($change)
        <p class="relative mt-1 flex items-center gap-1 text-xs font-medium {{ $trendClass }}">
            @if ($trend === 'up')<x-ui.icon name="arrow-trending-up" class="size-4 shrink-0" />@elseif ($trend === 'down')<x-ui.icon name="arrow-trending-down" class="size-4 shrink-0" />@endif
            {{ $change }}
        </p>
    @endif
</div>
