{{-- Logo chữ tạm thời cho tới khi có bộ nhận diện chính thức. inverse: dùng trên nền tối. --}}
@props(['inverse' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-bold '.($inverse ? 'text-white' : 'text-navy-900')]) }}>
    <svg viewBox="0 0 32 32" class="size-8" aria-hidden="true">
        <rect width="32" height="32" rx="10" @class(['fill-highlight' => $inverse, 'fill-navy-900' => ! $inverse])/>
        <path d="M10 22V10h11M10 16h8M10 22h11" stroke-width="2.6" stroke-linecap="round" fill="none" @class(['stroke-navy-900' => $inverse, 'stroke-highlight' => ! $inverse])/>
    </svg>
    <span class="text-xl tracking-tight">Ez<span @class(['text-highlight' => $inverse, 'text-primary-600' => ! $inverse])>Fin</span></span>
</span>
