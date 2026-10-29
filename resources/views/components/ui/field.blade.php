{{-- Khung chung cho ô nhập: nhãn, gợi ý, lỗi. --}}
@props(['label' => null, 'for', 'hint' => null, 'error' => null, 'required' => false])
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $for }}" class="block text-sm font-medium text-navy-900">
            {{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($error)
        <p id="{{ $for }}-error" class="flex items-center gap-1 text-sm text-red-600"><x-ui.icon name="exclamation-circle" class="size-4" />{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $for }}-hint" class="text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
