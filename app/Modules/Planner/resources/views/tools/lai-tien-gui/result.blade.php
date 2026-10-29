<div class="grid gap-3 sm:grid-cols-3">
    <x-ui.stat-card label="Tiền lãi" :value="Format::money($result->interest)" icon="arrow-trending-up" tone="income" />
    <x-ui.stat-card label="Tổng nhận cuối kỳ" :value="Format::money($result->total)" icon="banknotes" />
    @if ($result->monthlyPayout !== null)
        <x-ui.stat-card label="Lãi nhận mỗi tháng" :value="Format::money($result->monthlyPayout)" icon="calendar" tone="neutral" />
    @else
        <x-ui.stat-card label="Lãi bình quân mỗi tháng" :value="Format::money(intdiv($result->interest, $months))" icon="calendar" tone="neutral" />
    @endif
</div>

<x-ui.bar-chart :labels="array_column($result->months, 'month')" x-title="Tháng" label-prefix="Tháng "
    :caption="'Biểu đồ tiền lãi cộng dồn qua '.$months.' tháng. Số liệu chi tiết ở bảng bên dưới.'"
    :series="[['label' => 'Lãi cộng dồn', 'tone' => 'income', 'values' => array_column($result->months, 'accrued')]]" />

<section aria-labelledby="theo-thang">
    <h2 id="theo-thang" class="text-lg font-semibold">Theo từng tháng</h2>
    <x-ui.table class="mt-3">
        <thead>
            <tr><th>Tháng</th><th class="text-right">Lãi nhận trong tháng</th><th class="text-right">Lãi cộng dồn</th></tr>
        </thead>
        <tbody class="num">
            @foreach ($result->months as $row)
                <tr>
                    <td>{{ $row['month'] }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['interest']) }}</td>
                    <td class="text-right font-semibold whitespace-nowrap text-navy-900">{{ Format::money($row['accrued']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>
    @if ($result->monthlyPayout === null)
        <p class="mt-2 text-xs text-slate-500">Lĩnh lãi cuối kỳ: lãi cộng dồn là phần đã phát sinh, ngân hàng trả một lần vào cuối kỳ.</p>
    @endif
</section>

{{-- Trang so sánh tự bỏ kỳ hạn không có trong danh sách của nó. --}}
@if (\Laravel\Pennant\Feature::active('compare'))
    <x-ui.alert>
        Muốn biết ngân hàng nào trả lãi cao nhất cho số tiền này?
        <a href="{{ route('compare.savings.index', ['so_tien' => $result->amount, 'ky_han' => $months]) }}" class="font-medium underline">So sánh lãi suất tiết kiệm</a>
    </x-ui.alert>
@endif
