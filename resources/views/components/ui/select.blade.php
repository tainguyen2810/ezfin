@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'id' => null, 'required' => false])
@php
    $id ??= $name;
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required">
    <div class="relative">
        <select id="{{ $id }}" name="{{ $name }}" @required($required)
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->merge(['class' => 'block h-11 w-full appearance-none rounded-control border bg-white pl-3 pr-10 text-navy-900 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100 '.($error ? 'border-red-500' : 'border-slate-300')]) }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <x-ui.icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-500" />
    </div>
</x-ui.field>
