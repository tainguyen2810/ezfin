{{-- Hiển thị 1.000.000 khi gõ, gửi lên server giá trị số nguyên trong ô ẩn cùng tên. --}}
@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'id' => null, 'currency' => 'đ', 'required' => false])
@php
    $id ??= $name;
    $error = $errors->first($name);
    $initial = preg_replace('/\D/', '', (string) old($name, $value === null ? '' : (int) $value));
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required">
    <div class="relative" x-data="moneyInput(@js($initial))">
        <input id="{{ $id }}" type="text" inputmode="numeric" autocomplete="off"
            value="{{ $initial === '' ? '' : number_format((int) $initial, 0, ',', '.') }}"
            x-on:input="update($event)"
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            @required($required)
            {{ $attributes->merge(['class' => 'num block h-11 w-full rounded-lg border bg-white pl-3 pr-10 text-right text-navy-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100 '.($error ? 'border-red-500' : 'border-slate-300')]) }}>
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">{{ $currency }}</span>
        <input type="hidden" name="{{ $name }}" x-bind:value="raw" value="{{ $initial }}">
    </div>
</x-ui.field>
