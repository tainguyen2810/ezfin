<x-mail::message>
@if ($copy->greeting !== '')
# {{ $copy->greeting }}
@endif

{{ $copy->bodyHtml() }}

@if ($term)
- Báo khi lãi suất tiết kiệm **{{ $term }} tháng** cao nhất thị trường tăng
@endif
@if ($weekly)
- Bản tin tuần: lãi suất cao nhất và bài viết mới (sáng thứ Hai)
@endif

<x-mail::button :url="$confirmUrl">
{{ $copy->actionLabel }}
</x-mail::button>

Link có hiệu lực {{ \App\Modules\Newsletter\Actions\RequestSubscription::LINK_DAYS }} ngày. Nếu bạn không đăng ký, hãy bỏ qua thư này: EzFin sẽ không gửi thêm và không lưu địa chỉ email của bạn.

EzFin
</x-mail::message>
