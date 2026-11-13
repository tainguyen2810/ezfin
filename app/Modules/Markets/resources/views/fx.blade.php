@php
    $rateValue = fn ($value) => $value === null ? '—' : Format::decimal((float) $value, (float) $value < 1000 ? 2 : 0);
    $main = $batches[0];
@endphp
<x-layouts.public title="Tỷ giá ngoại tệ hôm nay"
    description="Tỷ giá mua tiền mặt, mua chuyển khoản và bán USD, EUR, JPY và các ngoại tệ khác theo bảng ngân hàng công bố, kèm giờ cập nhật, nguồn và biểu đồ 30 ngày."
    :canonical="route('markets.fx')" :noindex="request()->query() !== []">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Tỷ giá' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Tỷ giá ngoại tệ hôm nay</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Tỷ giá theo bảng ngân hàng công bố, đơn vị đồng cho một đơn vị ngoại tệ. EzFin cập nhật thủ công nên có thể chậm hơn bảng của ngân hàng; giá giao dịch thực tế do ngân hàng áp dụng tại thời điểm bạn giao dịch.</p>
        @if ($goldAvailable)
            <p class="mt-2 text-sm"><a href="{{ route('markets.gold') }}" class="font-semibold text-primary-700 underline underline-offset-4">Xem giá vàng hôm nay</a></p>
        @endif

        @foreach ($batches as $batch)
            <section class="mt-8" aria-labelledby="bang-{{ $loop->index }}">
                <h2 id="bang-{{ $loop->index }}" class="text-lg font-bold">Tỷ giá {{ $batch['source'] }}</h2>
                @include('markets::_freshness', ['batch' => $batch])
                <x-ui.table class="mt-3">
                    <thead>
                        <tr>
                            <th scope="col">Ngoại tệ</th>
                            <th scope="col" class="hidden text-right sm:table-cell">Mua tiền mặt</th>
                            <th scope="col" class="text-right">Mua chuyển khoản</th>
                            <th scope="col" class="text-right">Bán</th>
                            <th scope="col" class="text-right">So với hôm trước</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batch['rows'] as ['quote' => $quote, 'change' => $change])
                            <tr>
                                <th scope="row" class="whitespace-nowrap text-left font-semibold text-navy-900">{{ $quote->currency }} <span class="block text-xs font-normal text-slate-500">{{ $quote->currencyName() }}</span></th>
                                <td class="num hidden text-right sm:table-cell">{{ $rateValue($quote->buy_cash) }}</td>
                                <td class="num text-right">{{ $rateValue($quote->buy_transfer) }}</td>
                                <td class="num text-right font-semibold text-navy-900">{{ $rateValue($quote->sell) }}</td>
                                <td class="num whitespace-nowrap text-right">@include('markets::_change', ['change' => $change, 'money' => false])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </section>
        @endforeach

        <section class="mt-10" aria-labelledby="doi-tien">
            <h2 id="doi-tien" class="text-lg font-bold">Đổi ngoại tệ sang đồng</h2>
            <x-ui.card class="mt-3">
                <form method="GET" action="{{ route('markets.fx') }}" class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    <x-ui.input name="so_tien" label="Số ngoại tệ" inputmode="decimal" :value="$amount === null ? '' : rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.')" placeholder="100" />
                    <x-ui.select name="ma" label="Ngoại tệ" :options="$currencies" :value="$code" />
                    <x-ui.button type="submit">Tính</x-ui.button>
                </form>
                @if ($amount !== null)
                    <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2" role="status">
                        <div>
                            <dt class="text-sm text-slate-500">Bán {{ Format::decimal($amount, 2) }} {{ $code }} cho ngân hàng (giá mua chuyển khoản)</dt>
                            <dd class="num mt-1 text-xl font-bold text-navy-900">{{ ($rate->buy_transfer ?? $rate->buy_cash) === null ? '—' : Format::money($amount * (float) ($rate->buy_transfer ?? $rate->buy_cash)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500">Mua {{ Format::decimal($amount, 2) }} {{ $code }} từ ngân hàng (giá bán)</dt>
                            <dd class="num mt-1 text-xl font-bold text-navy-900">{{ Format::money($amount * (float) $rate->sell) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">Theo bảng {{ $main['source'] }} lúc {{ $main['quoted_at']->format('H:i') }} {{ Format::date($main['quoted_at']) }}, chưa gồm phí. Đây là ước tính để tham khảo.</p>
                @endif
            </x-ui.card>
        </section>

        @if (count($history) >= 2)
            <section class="mt-10" aria-labelledby="lich-su">
                <h2 id="lich-su" class="text-lg font-bold">Giá bán {{ $code }} 30 ngày qua</h2>
                <p class="mt-1 text-sm text-slate-600">Lần công bố cuối mỗi ngày của {{ $main['source'] }} mà EzFin đã lưu; ngày không có số liệu thì bỏ qua.</p>
                <x-ui.line-chart class="mt-3" :labels="array_map(fn ($point) => \Illuminate\Support\Carbon::parse($point['date'])->format('d/m'), $history)"
                    :caption="'Giá bán '.$code.' theo ngày'" :format="fn ($value) => Format::decimal($value, $value < 1000 ? 2 : 0)" :tick-format="fn ($tick) => Format::decimal($tick, $tick < 1000 ? 2 : 0)"
                    :series="[['label' => 'Giá bán '.$code, 'tone' => 'primary', 'values' => array_column($history, 'value')]]" />
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer font-medium text-primary-700">Xem bảng số liệu</summary>
                    <x-ui.table class="mt-2">
                        <thead><tr><th scope="col">Ngày</th><th scope="col" class="text-right">Giá bán</th></tr></thead>
                        <tbody>
                            @foreach (array_reverse($history) as $point)
                                <tr><td>{{ Format::date($point['date']) }}</td><td class="num text-right">{{ $rateValue($point['value']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                </details>
            </section>
        @endif

        <p class="mt-10 text-xs text-slate-500">Số liệu chỉ để tham khảo, không phải lời chào mua, chào bán ngoại tệ. Xem <a href="{{ route('legal.methodology') }}" class="underline">Phương pháp đánh giá</a> về cách EzFin thu thập số liệu.</p>
    </div>
</x-layouts.public>
