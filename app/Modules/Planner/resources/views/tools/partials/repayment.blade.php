{{-- Biểu đồ và bảng lịch trả nợ dùng chung cho khoản vay và trả góp. $schedule: RepaymentSchedule. --}}
@php
    $long = count($schedule->months) > 36;
    $chartRows = $long ? $schedule->byYear() : $schedule->months;
    $key = $long ? 'year' : 'month';
@endphp
<x-ui.bar-chart :labels="array_column($chartRows, $key)" :x-title="$long ? 'Năm' : 'Tháng'" :label-prefix="$long ? 'Năm ' : 'Tháng '"
    :caption="'Biểu đồ tiền gốc và tiền lãi phải trả theo '.($long ? 'từng năm' : 'từng tháng').'. Số liệu chi tiết ở bảng bên dưới.'"
    :series="[
        ['label' => 'Tiền gốc', 'tone' => 'primary', 'values' => array_column($chartRows, 'principal')],
        ['label' => 'Tiền lãi', 'tone' => 'cost', 'values' => array_column($chartRows, 'interest')],
    ]" />

<section aria-labelledby="lich-tra-no">
    <h2 id="lich-tra-no" class="text-lg font-semibold">Lịch trả nợ {{ $long ? 'theo năm' : 'theo tháng' }}</h2>
    <x-ui.table class="mt-3">
        <thead>
            <tr><th>{{ $long ? 'Năm' : 'Tháng' }}</th><th class="text-right">Tiền trả</th><th class="text-right">Gốc</th><th class="text-right">Lãi</th><th class="text-right">Dư nợ còn lại</th></tr>
        </thead>
        <tbody class="num">
            @foreach ($chartRows as $row)
                <tr>
                    <td>{{ $row[$key] }}</td>
                    <td class="text-right font-semibold whitespace-nowrap text-navy-900">{{ Format::money($row['payment']) }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['principal']) }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['interest']) }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['balance']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($long)
        <details class="mt-3 rounded-card border border-slate-200 bg-white">
            <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-primary-700">Xem lịch trả nợ từng tháng ({{ count($schedule->months) }} tháng)</summary>
            <x-ui.table class="rounded-none border-0 border-t">
                <thead>
                    <tr><th>Tháng</th><th class="text-right">Tiền trả</th><th class="text-right">Gốc</th><th class="text-right">Lãi</th><th class="text-right">Dư nợ còn lại</th></tr>
                </thead>
                <tbody class="num">
                    @foreach ($schedule->months as $row)
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td class="text-right whitespace-nowrap">{{ Format::money($row['payment']) }}</td>
                            <td class="text-right whitespace-nowrap">{{ Format::money($row['principal']) }}</td>
                            <td class="text-right whitespace-nowrap">{{ Format::money($row['interest']) }}</td>
                            <td class="text-right whitespace-nowrap">{{ Format::money($row['balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </details>
    @endif
</section>
