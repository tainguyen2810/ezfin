@php
    $flat = $schedule->method === \App\Modules\Planner\Enums\RepaymentMethod::Flat;
    [$flatSchedule, $reducingSchedule] = $flat ? [$schedule, $alternative] : [$alternative, $schedule];
@endphp
<div class="grid gap-3 sm:grid-cols-3">
    <x-ui.stat-card :label="$flat ? 'Trả mỗi tháng' : 'Trả tháng đầu (cao nhất)'" :value="Format::money($schedule->firstPayment())" icon="calendar" />
    <x-ui.stat-card label="Tổng tiền lãi" :value="Format::money($schedule->interest)" icon="arrow-trending-up" tone="expense" />
    <x-ui.stat-card label="Tổng chi phí sản phẩm" :value="Format::money($downPayment + $schedule->total)" icon="shopping-bag" tone="neutral" />
</div>

<x-ui.card>
    <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div><dt class="text-slate-500">Trả trước</dt><dd class="num mt-0.5 font-semibold text-navy-900">{{ Format::money($downPayment) }}</dd></div>
        <div><dt class="text-slate-500">Số tiền vay</dt><dd class="num mt-0.5 font-semibold text-navy-900">{{ Format::money($schedule->amount) }}</dd></div>
        <div><dt class="text-slate-500">Chênh lệch so với mua thẳng</dt><dd class="num mt-0.5 font-semibold text-navy-900">{{ Format::money($downPayment + $schedule->total - $price) }}</dd></div>
    </dl>
</x-ui.card>

@if ($flatSchedule->effectiveRate !== null)
    <section aria-labelledby="lai-phang-va-du-no" class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-navy-900">
        <h2 id="lai-phang-va-du-no" class="font-semibold">Lãi phẳng và dư nợ giảm dần</h2>
        <p class="mt-1">
            Lãi phẳng {{ Format::rate($rate) }} tương đương khoảng <strong>{{ Format::rate($flatSchedule->effectiveRate) }}</strong> nếu tính trên dư nợ giảm dần. Cùng mức {{ Format::rate($rate) }}: lãi phẳng tốn {{ Format::money($flatSchedule->interest) }} tiền lãi, dư nợ giảm dần tốn {{ Format::money($reducingSchedule->interest) }}.
        </p>
    </section>
@endif

@include('planner::tools.partials.repayment', ['schedule' => $schedule])
