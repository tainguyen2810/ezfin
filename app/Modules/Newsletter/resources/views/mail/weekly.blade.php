<x-mail::message>
# Bản tin EzFin tuần {{ now()->format('d/m/Y') }}

@if ($rates)
## Lãi suất tiết kiệm cao nhất

<x-mail::table>
| Kỳ hạn | Cao nhất | Ngân hàng | So với tuần trước |
| :--- | ---: | :--- | ---: |
@foreach ($rates as $row)
@php($change = $row['previous'] === null ? null : round($row['rate'] - $row['previous'], 2))
| [{{ $row['term'] }} tháng]({{ route('compare.savings.term', $row['term']) }}) | **{{ Format::percent($row['rate']) }}** | {{ $row['institution'] }} | {{ $change === null ? '—' : ($change == 0 ? 'Không đổi' : ($change > 0 ? '▲ ' : '▼ ').Format::decimal(abs($change)).' điểm') }} |
@endforeach
</x-mail::table>

Đơn vị %/năm, ở số tiền gửi thấp nhất ngân hàng công bố. Lãi suất có thể thay đổi; hãy xác nhận với ngân hàng trước khi gửi.
@endif

@if ($articles)
## Bài viết mới

@foreach ($articles as $article)
**[{{ $article['title'] }}]({{ $article['url'] }})**
@if ($article['excerpt'])
{{ $article['excerpt'] }}
@endif

@endforeach
@endif

@include('newsletter::mail._footer')
</x-mail::message>
