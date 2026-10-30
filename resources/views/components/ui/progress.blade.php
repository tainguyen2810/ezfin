{{-- Thanh tiến độ ngân sách/mục tiêu. Màu theo tỷ lệ: < 80% xanh, 80–100% cam, > 100% đỏ (mode="budget");
     mode="goal" luôn xanh dương. --}}
@props(['value', 'max' => 100, 'label' => null, 'detail' => null, 'mode' => 'budget'])
@php
    $percent = $max > 0 ? round($value / $max * 100) : 0;
    $color = $mode === 'goal'
        ? 'bg-primary-500'
        : ($percent > 100 ? 'bg-expense' : ($percent >= 80 ? 'bg-warning' : 'bg-income'));
    $textColor = $mode === 'budget' && $percent > 100 ? 'text-expense' : 'text-navy-900';
@endphp
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <div class="flex items-baseline justify-between gap-3 text-sm">
            <span class="font-medium text-navy-900">{{ $label }}</span>
            <span class="num font-semibold {{ $textColor }}">{{ $percent }}%</span>
        </div>
    @endif
    <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min($percent, 100) }}" @if ($label) aria-label="{{ $label }}" @endif>
        <div class="h-full rounded-full {{ $color }}" style="width: {{ min($percent, 100) }}%"></div>
    </div>
    @if ($detail || ! $label)
        <div class="flex items-baseline justify-between gap-3 text-xs">
            <p class="num text-slate-500">{{ $detail }}</p>
            @unless ($label)<span class="num font-semibold {{ $textColor }}">{{ $percent }}%</span>@endunless
        </div>
    @endif
</div>
