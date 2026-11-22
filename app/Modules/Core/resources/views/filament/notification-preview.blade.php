{{-- Xem trước với dữ liệu mẫu, cập nhật khi đang gõ. --}}
<div class="ez-notify-preview">
    <p class="ez-notify-label">Xem trước (dữ liệu mẫu)</p>

    @if ($error)
        <p class="ez-notify-error">Không dựng được bản xem trước: {{ $error }}</p>
    @elseif ($channel === \App\Modules\Core\Notifications\Channel::Mail)
        <p class="ez-notify-muted">Tiêu đề: <strong>{{ $message->subject }}</strong></p>
        <iframe title="Xem trước email" sandbox="" srcdoc="{{ $html }}" class="ez-notify-frame"></iframe>
    @else
        <div class="ez-notify-bell">
            <p class="ez-notify-bell-title">{!! str($message->subject)->sanitizeHtml() !!}</p>
            @if (filled($message->body))
                <p class="ez-notify-muted">{!! str($message->body)->sanitizeHtml() !!}</p>
            @endif
            @if ($message->actionLabel !== '')
                <p class="ez-notify-link">{{ $message->actionLabel }}</p>
            @endif
        </div>
    @endif
</div>
