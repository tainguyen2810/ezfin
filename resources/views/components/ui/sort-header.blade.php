{{-- Tiêu đề cột sắp xếp được, giữ các tham số lọc khác trên URL. --}}
@props(['column', 'label', 'align' => 'left'])
@php
    $current = request('sap_xep');
    $direction = request('chieu', 'asc');
    $active = $current === $column;
    $next = $active && $direction === 'asc' ? 'desc' : 'asc';
@endphp
<th scope="col" @class(['text-right' => $align === 'right']) aria-sort="{{ $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a href="{{ request()->fullUrlWithQuery(['sap_xep' => $column, 'chieu' => $next, 'page' => null]) }}" class="inline-flex items-center gap-1 hover:text-navy-900">
        {{ $label }}
        <x-ui.icon name="chevron-down" @class(['size-3.5 transition', 'rotate-180' => $active && $direction === 'asc', 'opacity-30' => ! $active]) />
    </a>
</th>
