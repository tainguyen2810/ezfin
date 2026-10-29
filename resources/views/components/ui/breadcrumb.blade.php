{{-- items: ['Trang chủ' => url, 'Thẻ tín dụng' => null (trang hiện tại)] --}}
@props(['items'])
<nav {{ $attributes->merge(['class' => 'text-sm']) }} aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1 text-slate-500">
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1">
                @if (! $loop->first)<x-ui.icon name="chevron-right" class="size-3.5 text-slate-400" />@endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-primary-700">{{ $label }}</a>
                @else
                    <span class="font-medium text-navy-900" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
