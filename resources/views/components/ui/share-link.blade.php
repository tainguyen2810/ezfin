{{-- Liên kết chia sẻ: ô chỉ đọc chọn sẵn để sao chép; nút "Sao chép" chỉ hiện khi có JS. --}}
@props(['url', 'label' => 'Liên kết chia sẻ', 'id' => 'lien-ket-chia-se'])
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }} x-data="{
    copy() {
        this.$refs.url.select();
        const done = () => window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Đã sao chép liên kết.', type: 'success' } }));
        (navigator.clipboard ? navigator.clipboard.writeText(this.$refs.url.value) : Promise.reject())
            .then(done)
            .catch(() => document.execCommand('copy') && done());
    },
}">
    <label for="{{ $id }}" class="block text-sm font-medium text-navy-900">{{ $label }}</label>
    <div class="flex gap-2">
        <input id="{{ $id }}" type="text" readonly value="{{ $url }}" x-ref="url" x-on:focus="$el.select()"
            class="block h-11 min-w-0 flex-1 rounded-control border border-slate-300 bg-slate-50 px-3 text-sm text-slate-700 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-100">
        <x-ui.button type="button" variant="outline" x-cloak x-on:click="copy()">Sao chép</x-ui.button>
    </div>
</div>
