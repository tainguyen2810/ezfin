<div class="grid gap-3 sm:grid-cols-3">
    <x-ui.stat-card :label="'Số tiền sau '.$years.' năm'" :value="Format::money($result->balance)" icon="banknotes" />
    <x-ui.stat-card label="Tổng tiền bỏ vào" :value="Format::money($result->contributed)" icon="wallet" tone="neutral" />
    <x-ui.stat-card label="Tiền lãi" :value="Format::money($result->interest)" icon="arrow-trending-up" tone="income" />
</div>

<section aria-labelledby="theo-nam">
    <h2 id="theo-nam" class="text-lg font-semibold">Theo từng năm</h2>
    <x-ui.table class="mt-3">
        <thead>
            <tr><th>Năm</th><th class="text-right">Tiền bỏ vào</th><th class="text-right">Lãi trong năm</th><th class="text-right">Tổng lãi</th><th class="text-right">Số dư cuối năm</th></tr>
        </thead>
        <tbody class="num">
            @foreach ($result->years as $row)
                <tr>
                    <td>{{ $row['year'] }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['contributed']) }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['interest']) }}</td>
                    <td class="text-right whitespace-nowrap">{{ Format::money($row['totalInterest']) }}</td>
                    <td class="text-right font-semibold whitespace-nowrap text-navy-900">{{ Format::money($row['balance']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>
</section>
