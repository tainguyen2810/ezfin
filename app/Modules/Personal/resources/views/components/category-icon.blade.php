{{-- Ô icon màu của danh mục; chuyển tiền (không có danh mục) dùng icon mũi tên. --}}
@props(['category' => null, 'size' => 'md'])
@php($box = $size === 'sm' ? 'size-8 rounded-lg' : 'size-10 rounded-xl')
<span {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center $box ".($category?->colorClasses() ?? 'bg-slate-100 text-slate-600')]) }}>
    <x-ui.icon :name="$category?->icon ?? 'arrows-right-left'" :class="$size === 'sm' ? 'size-4' : 'size-5'" />
</span>
