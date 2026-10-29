@php $equalPrincipal = $schedule->method === \App\Modules\Planner\Enums\RepaymentMethod::EqualPrincipal; @endphp
<div class="grid gap-3 sm:grid-cols-3">
    <x-ui.stat-card :label="$equalPrincipal ? 'Trả tháng đầu (cao nhất)' : 'Trả mỗi tháng'" :value="Format::money($schedule->firstPayment())" icon="calendar" />
    <x-ui.stat-card label="Tổng tiền lãi" :value="Format::money($schedule->interest)" icon="arrow-trending-up" tone="expense" />
    <x-ui.stat-card label="Tổng phải trả" :value="Format::money($schedule->total)" icon="banknotes" tone="neutral" />
</div>

<x-ui.alert>
    @if ($equalPrincipal)
        Tiền trả giảm dần, tháng cuối còn {{ Format::money($schedule->months[array_key_last($schedule->months)]['payment']) }}.
        Nếu chọn trả đều hằng tháng: {{ Format::money($alternative->firstPayment()) }}/tháng, tổng lãi {{ Format::money($alternative->interest) }} ({{ Format::money($alternative->interest - $schedule->interest) }} nhiều hơn).
    @else
        Nếu chọn dư nợ giảm dần: tháng đầu trả {{ Format::money($alternative->firstPayment()) }}, tổng lãi {{ Format::money($alternative->interest) }} ({{ Format::money($schedule->interest - $alternative->interest) }} ít hơn) nhưng những tháng đầu trả nhiều hơn.
    @endif
</x-ui.alert>

@include('planner::tools.partials.repayment', ['schedule' => $schedule])
