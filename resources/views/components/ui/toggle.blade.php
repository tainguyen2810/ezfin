@props(['name', 'label', 'checked' => false, 'id' => null, 'description' => null])
@php($id ??= $name)
<label for="{{ $id }}" class="flex cursor-pointer items-start justify-between gap-4">
    <span>
        <span class="block text-sm font-medium text-navy-900">{{ $label }}</span>
        @if ($description)<span class="block text-sm text-slate-500">{{ $description }}</span>@endif
    </span>
    <span class="relative mt-0.5 inline-flex shrink-0">
        <input type="hidden" name="{{ $name }}" value="0">
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" role="switch" @checked(old($name, $checked))
            {{ $attributes->merge(['class' => 'peer sr-only']) }}>
        <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-primary-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary-600 peer-disabled:opacity-50"></span>
        <span class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
