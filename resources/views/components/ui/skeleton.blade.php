@props(['lines' => 3])
<div {{ $attributes->merge(['class' => 'animate-pulse space-y-3']) }} aria-busy="true" aria-live="polite">
    <span class="sr-only">Đang tải…</span>
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-3 rounded bg-slate-200" style="width: {{ [100, 85, 65, 90, 75][$i % 5] }}%"></div>
    @endfor
</div>
