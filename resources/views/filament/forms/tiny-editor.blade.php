@php
    $statePath = $getStatePath();
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @vite('resources/js/tinymce-editor.js')
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
            options: @js($getEditorOptions()),
            disabled: @js($isDisabled()),
            upload(file) {
                return new Promise((resolve, reject) => {
                    $wire.upload(@js('componentFileAttachments.'.$statePath), file,
                        () => $wire.callSchemaComponentMethod(@js($getKey()), 'saveUploadedFileAttachmentAndGetUrl')
                            .then((url) => url ? resolve(url) : reject('Không lưu được ảnh. Ảnh phải là PNG, JPG hoặc WebP và không quá dung lượng cho phép.')),
                        () => reject('Tải ảnh lên thất bại.'))
                })
            },
            init() {
                const mount = () => window.EzfinTiny ? window.EzfinTiny.mount(this) : setTimeout(mount, 50)
                mount()
            },
            destroy() {
                window.EzfinTiny?.unmount(this)
            },
        }"
        wire:ignore
        class="ezfin-tiny-editor"
        data-tiny-editor="{{ $statePath }}"
    >
        <textarea x-ref="textarea" id="{{ $getId() }}" class="block w-full" style="min-height: {{ $getEditorOptions()['height'] }}px; visibility: hidden"></textarea>
    </div>
</x-dynamic-component>
