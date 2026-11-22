<x-mail::message>
@if ($copy->greeting !== '')
# {{ $copy->greeting }}
@endif

{{ $copy->bodyHtml() }}

<x-mail::table>
| Kỳ hạn | Trước | Hiện tại | Ngân hàng |
| :--- | ---: | ---: | :--- |
@foreach ($increases as $item)
| {{ $item['term'] }} tháng | {{ Format::percent($item['from']) }} | **{{ Format::percent($item['to']) }}** | {{ $item['institution']->short_name ?: $item['institution']->name }} |
@endforeach
</x-mail::table>

@php($first = $increases[0])
<x-mail::button :url="route('compare.savings.term', $first['term'])">
{{ $copy->actionLabel }}
</x-mail::button>

Đơn vị %/năm, mức cao nhất ở số tiền gửi thấp nhất ngân hàng công bố. Lãi suất có thể thay đổi; hãy xác nhận với ngân hàng trước khi gửi. Đây không phải khuyến nghị tài chính cá nhân.

@include('newsletter::mail._footer')
</x-mail::message>
