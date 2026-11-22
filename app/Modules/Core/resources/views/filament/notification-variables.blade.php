{{-- Biến chèn được: bấm để chèn vào ô đang soạn (mousedown.prevent giữ con trỏ trong ô). Kiểu dáng: ez-notify-* trong resources/css/admin.css. --}}
@if ($variables !== [])
    <div
        x-data="{
            last: null,
            insert(token) {
                if (! this.last || ! document.body.contains(this.last)) return
                this.last.focus()
                document.execCommand('insertText', false, token)
            },
        }"
        x-on:focusin.window="if ($event.target.matches('[data-notification-template]')) last = $event.target"
        class="ez-notify-vars"
    >
        @foreach ($variables as $name => [$label, $sample])
            <button type="button" class="ez-notify-var" x-on:mousedown.prevent="insert(@js('{'.$name.'}'))" title="Ví dụ: {{ $sample }}">
                <code>{{ '{'.$name.'}' }}</code> <span>{{ $label }}</span>
            </button>
        @endforeach
    </div>
@else
    <p class="ez-notify-muted">Loại này không có biến chèn.</p>
@endif
