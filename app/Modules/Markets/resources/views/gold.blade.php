<x-layouts.public title="Giá vàng hôm nay"
    description="Giá mua, giá bán vàng miếng và vàng nhẫn theo bảng doanh nghiệp vàng công bố, đơn vị đồng/lượng, kèm giờ cập nhật, nguồn và biểu đồ 30 ngày."
    :canonical="route('markets.gold')">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Giá vàng' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Giá vàng hôm nay</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Giá theo bảng doanh nghiệp vàng công bố, đơn vị đồng/lượng. EzFin cập nhật thủ công nên có thể chậm hơn bảng giá tại cửa hàng; giá giao dịch thực tế do nơi bán áp dụng tại thời điểm bạn mua bán.</p>
        @if ($fxAvailable)
            <p class="mt-2 text-sm"><a href="{{ route('markets.fx') }}" class="font-semibold text-primary-700 underline underline-offset-4">Xem tỷ giá ngoại tệ</a></p>
        @endif

        @foreach ($batches as $batch)
            <section class="mt-8" aria-labelledby="bang-{{ $loop->index }}">
                <h2 id="bang-{{ $loop->index }}" class="text-lg font-bold">Giá vàng {{ $batch['source'] }}</h2>
                @include('markets::_freshness', ['batch' => $batch])
                <x-ui.table class="mt-3">
                    <thead>
                        <tr>
                            <th scope="col">Loại vàng</th>
                            <th scope="col" class="text-right">Mua vào</th>
                            <th scope="col" class="text-right">Bán ra</th>
                            <th scope="col" class="text-right">Giá bán so với hôm trước</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batch['rows'] as ['quote' => $quote, 'change' => $change])
                            <tr>
                                <th scope="row" class="text-left font-semibold text-navy-900">{{ $quote->product }}</th>
                                <td class="num whitespace-nowrap text-right">{{ Format::money($quote->buy) }}</td>
                                <td class="num whitespace-nowrap text-right font-semibold text-navy-900">{{ Format::money($quote->sell) }}</td>
                                <td class="num whitespace-nowrap text-right">@include('markets::_change', ['change' => $change, 'money' => true])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </section>
        @endforeach

        @if (count($history) >= 2)
            <section class="mt-10" aria-labelledby="lich-su">
                <h2 id="lich-su" class="text-lg font-bold">Giá bán {{ $main->product }} 30 ngày qua</h2>
                <p class="mt-1 text-sm text-slate-600">Lần công bố cuối mỗi ngày của {{ $main->source }} mà EzFin đã lưu, đơn vị triệu đồng/lượng; ngày không có số liệu thì bỏ qua.</p>
                <x-ui.line-chart class="mt-3" :labels="array_map(fn ($point) => \Illuminate\Support\Carbon::parse($point['date'])->format('d/m'), $history)"
                    :caption="'Giá bán '.$main->product.' theo ngày'" :format="fn ($value) => Format::decimal($value, 2).' tr'" :tick-format="fn ($tick) => Format::decimal($tick, 1)"
                    :series="[['label' => 'Giá bán (triệu đồng/lượng)', 'tone' => 'primary', 'values' => array_map(fn ($point) => round($point['value'] / 1_000_000, 2), $history)]]" />
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer font-medium text-primary-700">Xem bảng số liệu</summary>
                    <x-ui.table class="mt-2">
                        <thead><tr><th scope="col">Ngày</th><th scope="col" class="text-right">Giá bán</th></tr></thead>
                        <tbody>
                            @foreach (array_reverse($history) as $point)
                                <tr><td>{{ Format::date($point['date']) }}</td><td class="num text-right">{{ Format::money($point['value']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                </details>
            </section>
        @endif

        <p class="mt-10 text-xs text-slate-500">Số liệu chỉ để tham khảo, không phải lời chào mua, chào bán vàng và không phải khuyến nghị đầu tư. Xem <a href="{{ route('legal.methodology') }}" class="underline">Phương pháp đánh giá</a> về cách EzFin thu thập số liệu.</p>
    </div>
</x-layouts.public>
