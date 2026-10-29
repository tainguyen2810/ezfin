<div class="grid gap-3 sm:grid-cols-3">
    <x-ui.stat-card :label="'Nhận về sau '.$months.' tháng'" :value="Format::money($result->total)" icon="banknotes" />
    <x-ui.stat-card label="Tổng tiền đã gửi" :value="Format::money($result->deposited)" icon="wallet" tone="neutral" />
    <x-ui.stat-card label="Tiền lãi" :value="Format::money($result->interest)" icon="arrow-trending-up" tone="income" />
</div>

<x-ui.bar-chart :labels="array_column($chartRows, 'month')" x-title="Tháng" label-prefix="Tháng "
    :caption="'Biểu đồ tiền đã gửi và tiền lãi cộng dồn qua '.$months.' tháng. Số liệu chi tiết ở bảng bên dưới.'"
    :series="[
        ['label' => 'Tiền đã gửi', 'tone' => 'primary', 'values' => array_column($chartRows, 'deposited')],
        ['label' => 'Lãi cộng dồn', 'tone' => 'income', 'values' => array_column($chartRows, 'interest')],
    ]" />

<section aria-labelledby="theo-thang">
    <h2 id="theo-thang" class="text-lg font-semibold">Theo từng tháng</h2>
    <x-ui.table class="mt-3">
        <thead>
            <tr><th>Tháng</th><th class="text-right">Tiền đã gửi</th><th class="text-right">Lãi cộng dồn</th><th class="text-right">Tổng</th></tr>
        </thead>
        <tbody class="num">
            @foreach ($result->months as $row)
                <tr>
                    <td>{{ $row['month'] }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['deposited']) }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['interest']) }}</td>
                    <td class="text-right font-semibold whitespace-nowrap text-navy-900">{{ Format::money($row['balance']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>
    <p class="mt-2 text-xs text-slate-500">Lãi cộng dồn là phần đã phát sinh đến cuối tháng; ngân hàng trả một lần vào cuối kỳ.</p>
</section>
