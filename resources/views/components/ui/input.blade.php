@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'id' => null, 'required' => false])
@php
    $id ??= $name;
    $error = $errors->first($name);
    $describedBy = $error ? "$id-error" : ($hint ? "$id-hint" : null);
@endphp
<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :required="$required">
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        @required($required)
        {{ $attributes->merge(['class' => 'block h-11 w-full rounded-control border bg-white px-3 text-navy-900 placeholder:text-slate-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100 disabled:bg-slate-100 disabled:text-slate-500 '.($error ? 'border-red-500' : 'border-slate-300')]) }}>
</x-ui.field>
