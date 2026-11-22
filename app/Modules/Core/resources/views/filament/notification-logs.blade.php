@if ($logs->isEmpty())
    <p class="ez-notify-muted">Chưa gửi lần nào.</p>
@else
    <ul class="ez-notify-logs">
        @foreach ($logs as $log)
            <li>
                <span class="ez-notify-muted">{{ $log->created_at->format('H:i d/m/Y') }}</span>
                <span>{{ \App\Modules\Core\Notifications\Channel::tryFrom($log->channel)?->label() ?? $log->channel }}</span>
                <span class="ez-notify-muted">{{ $log->recipients }} người nhận{{ $log->test ? ' (gửi thử)' : '' }}</span>
                @if ($log->status === 'sent')
                    <span class="ez-notify-ok">Đã gửi</span>
                @else
                    <span class="ez-notify-error">Lỗi: {{ $log->error }}</span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
