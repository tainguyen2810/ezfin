{{-- Tab dạng link (giữ trạng thái trên URL). items: [['label' => ..., 'href' => ..., 'active' => bool]] --}}
@props(['items', 'label' => 'Chọn mục'])
<nav {{ $attributes->merge(['class' => 'flex gap-1 rounded-xl bg-slate-100 p-1']) }} aria-label="{{ $label }}">
    @foreach ($items as $item)
        <a href="{{ $item['href'] }}" @if ($item['active'] ?? false) aria-current="page" @endif
            @class([
                'flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                'bg-white text-primary-700 shadow-sm' => $item['active'] ?? false,
                'text-slate-600 hover:text-navy-900' => ! ($item['active'] ?? false),
            ])>{{ $item['label'] }}</a>
    @endforeach
</nav>
