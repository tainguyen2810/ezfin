{{-- Nguồn và giờ công bố của một bảng số liệu. --}}
<p class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-slate-600">
    <x-ui.icon name="clock" class="size-4 text-slate-400" />
    <span>{{ $batch['source'] }} công bố lúc <strong class="num text-navy-900">{{ $batch['quoted_at']->format('H:i') }} {{ Format::date($batch['quoted_at']) }}</strong></span>
    @if ($batch['source_url'])
        <a href="{{ $batch['source_url'] }}" rel="nofollow noopener" target="_blank" class="underline underline-offset-4">Xem nguồn</a>
    @endif
</p>
@if ($batch['stale'])
    <x-ui.alert variant="warning" class="mt-2">Số liệu của {{ $batch['source'] }} chưa được cập nhật hơn {{ \App\Modules\Markets\Actions\MarketBoard::STALE_DAYS }} ngày. Hãy xem giá mới nhất tại nguồn.</x-ui.alert>
@endif
