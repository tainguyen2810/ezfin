<x-mail::message>
# Lãi suất tiết kiệm vừa tăng

Mức lãi cao nhất EzFin ghi nhận cho kỳ hạn bạn theo dõi đã tăng:

<x-mail::table>
| Kỳ hạn | Trước | Hiện tại | Ngân hàng |
| :--- | ---: | ---: | :--- |
@foreach ($increases as $item)
| {{ $item['term'] }} tháng | {{ Format::percent($item['from']) }} | **{{ Format::percent($item['to']) }}** | {{ $item['institution']->short_name ?: $item['institution']->name }} |
@endforeach
</x-mail::table>

@php($first = $increases[0])
<x-mail::button :url="route('compare.savings.term', $first['term'])">
Xem lãi suất {{ $first['term'] }} tháng các ngân hàng
</x-mail::button>

Đơn vị %/năm, mức cao nhất ở số tiền gửi thấp nhất ngân hàng công bố. Lãi suất có thể thay đổi; hãy xác nhận với ngân hàng trước khi gửi. Đây không phải khuyến nghị tài chính cá nhân.

@include('newsletter::mail._footer')
</x-mail::message>
