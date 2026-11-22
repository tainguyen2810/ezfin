<x-mail::message>
@if ($copy->greeting !== '')
# {{ $copy->greeting }}
@endif

{{ $copy->bodyHtml() }}

<x-mail::table>
| Khoản | Ngày đến hạn | Số tiền |
| :--- | :--- | ---: |
@foreach ($items as $item)
| {{ $item->name }}{{ $item->mode === \App\Modules\Personal\Enums\RecurringMode::Auto ? ' (tự ghi)' : '' }} | {{ Format::date($item->next_due_on) }}{{ $item->next_due_on->isSameDay($today) ? ' (hôm nay)' : '' }} | {{ $item->type->isInflow() ? '+' : '−' }}{{ Format::money($item->amountInt()) }} |
@endforeach
</x-mail::table>

Khoản có chữ "tự ghi" sẽ được EzFin ghi vào ví vào ngày đến hạn. Với các khoản còn lại, sau khi thanh toán bạn bấm "Đã trả" trong ứng dụng.

<x-mail::button :url="route('personal.recurring.index')">
{{ $copy->actionLabel }}
</x-mail::button>

Bạn nhận email này vì đã bật nhắc hạn cho các khoản trên. Tắt nhắc trong phần sửa từng khoản.

EzFin
</x-mail::message>
