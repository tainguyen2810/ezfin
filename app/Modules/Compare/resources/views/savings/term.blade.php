{{-- Trang đích "Lãi suất tiết kiệm X tháng cao nhất tháng mm/yyyy". Mọi số liệu lấy từ biểu lãi đang hiệu lực,
     tiêu đề theo tháng hiện tại nên trang tự cập nhật khi ngân hàng đổi lãi suất hoặc sang tháng mới. --}}
@php
    use App\Modules\Compare\Actions\BestSavingsRates;
    use App\Modules\Compare\Models\SavingsRate;
    use App\Modules\Compare\Support\SavingsInterest;

    $termLabel = SavingsRate::termLabel($months);
    $monthLabel = now()->format('m/Y');
    $top = $rows->first();
    $topName = fn ($row) => $row['institution']->short_name ?: $row['institution']->name;
    $average = round($rows->avg('best'), 2);
    $sampleInterest = SavingsInterest::simple($sampleAmount, $top['best'], $months);
    $counterBest = $rows->max('counter');
    $onlineBest = $rows->max('online');
    $leaders = $rows->where('best', $top['best'])->map($topName)->values();

    $faq = [
        [
            'question' => 'Ngân hàng nào có lãi suất tiết kiệm '.$termLabel.' cao nhất tháng '.$monthLabel.'?',
            'answer' => 'Theo dữ liệu EzFin cập nhật ngày '.Format::date($updatedAt).', mức cao nhất cho kỳ hạn '.$termLabel.' là '.Format::rate($top['best']).' tại '.$leaders->join(', ', ' và ').'. '
                .'Mức này áp dụng cho số tiền gửi thấp nhất ngân hàng công bố; một số ngân hàng có mức cao hơn cho khoản gửi lớn. Trung bình '.$rows->count().' ngân hàng là '.Format::rate($average).'.',
        ],
        [
            'question' => 'Gửi '.Format::money($sampleAmount).' kỳ hạn '.$termLabel.' nhận được bao nhiêu tiền lãi?',
            'answer' => 'Với lãi suất '.Format::rate($top['best']).', lĩnh lãi cuối kỳ, tiền lãi khoảng '.Format::money($sampleInterest).' sau '.$termLabel.' '
                .'(tiền lãi = số tiền × lãi suất × số tháng / 12). Số thực tế theo cách tính của từng ngân hàng.',
        ],
    ];
    if ($onlineBest !== null && $counterBest !== null) {
        $faq[] = [
            'question' => 'Gửi tiết kiệm online hay tại quầy có lãi cao hơn?',
            'answer' => 'Kỳ hạn '.$termLabel.', mức cao nhất khi gửi online là '.Format::rate($onlineBest).', tại quầy là '.Format::rate($counterBest).'. '
                .'Nhiều ngân hàng trả lãi online cao hơn vì chi phí vận hành thấp hơn. Tiền gửi online vẫn được bảo hiểm tiền gửi như gửi tại quầy.',
        ];
    }
    $faq[] = [
        'question' => 'Rút tiền trước hạn thì được tính lãi thế nào?',
        'answer' => 'Phần lớn ngân hàng tính lãi không kỳ hạn (thường rất thấp) cho số tiền rút trước hạn, nên bạn có thể mất gần hết tiền lãi. Một số ngân hàng cho rút một phần. Hãy đọc điều kiện của sản phẩm trước khi gửi.',
    ];
    $faq[] = [
        'question' => 'Lãi suất trên EzFin được cập nhật khi nào?',
        'answer' => 'EzFin lấy lãi suất từ biểu lãi ngân hàng công bố, ghi nguồn và ngày xác minh cho từng sản phẩm. Trang này tự cập nhật khi có biểu lãi mới. Lãi suất có thể thay đổi bất kỳ lúc nào; hãy xác nhận với ngân hàng trước khi gửi.',
    ];

    $itemList = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Lãi suất tiết kiệm '.$termLabel.' cao nhất tháng '.$monthLabel,
        'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
        'numberOfItems' => $rows->count(),
        'itemListElement' => $rows->values()->map(fn ($row, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $row['institution']->name.' — '.Format::rate($row['best']),
            'url' => route('compare.institutions.show', $row['institution']->slug),
        ])->all(),
    ];
@endphp
<x-layouts.public :title="'Lãi suất tiết kiệm '.$termLabel.' cao nhất tháng '.$monthLabel"
    :description="'So sánh lãi suất tiết kiệm kỳ hạn '.$termLabel.' của '.$rows->count().' ngân hàng, cao nhất '.Format::rate($top['best']).' ('.$topName($top).'). Online và tại quầy, tiền lãi trên '.Format::money($sampleAmount).', cập nhật '.Format::date($updatedAt).'.'"
    :canonical="route('compare.savings.term', $months)">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($itemList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Lãi suất tiết kiệm' => route('compare.savings.index'), 'Kỳ hạn '.$termLabel => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Lãi suất tiết kiệm {{ $termLabel }} cao nhất tháng {{ $monthLabel }}</h1>
        <p class="mt-2 max-w-3xl text-slate-600">
            Cập nhật ngày {{ Format::date($updatedAt) }}: {{ $rows->count() }} ngân hàng đang công bố kỳ hạn {{ $termLabel }}.
            Cao nhất là <strong class="text-navy-900">{{ $leaders->join(', ', ' và ') }}</strong> với <strong class="text-navy-900">{{ Format::rate($top['best']) }}</strong>.
            Bảng xếp theo mức cao nhất của mỗi ngân hàng, ở số tiền gửi thấp nhất, lĩnh lãi cuối kỳ hoặc hình thức có lãi cao nhất.
        </p>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <x-ui.stat-card label="Cao nhất kỳ hạn {{ $termLabel }}" :value="Format::rate($top['best'])" icon="arrow-trending-up" :dark="true" />
            <x-ui.stat-card label="Tiền lãi trên {{ Format::money($sampleAmount) }}" :value="Format::money($sampleInterest)" icon="banknotes" />
            <x-ui.stat-card label="Trung bình {{ $rows->count() }} ngân hàng" :value="Format::rate($average)" icon="chart-bar" tone="neutral" />
        </div>

        <x-ui.table class="mt-6">
            <caption class="sr-only">Lãi suất tiết kiệm kỳ hạn {{ $termLabel }} theo ngân hàng, %/năm</caption>
            <thead>
                <tr>
                    <th scope="col" class="sticky left-0 bg-slate-50">Ngân hàng</th>
                    <th scope="col" class="text-right">Online</th>
                    <th scope="col" class="text-right whitespace-nowrap">Tại quầy</th>
                    <th scope="col" class="text-right whitespace-nowrap">Lãi trên {{ Format::compactMoney($sampleAmount) }}</th>
                    <th scope="col" class="text-right whitespace-nowrap">So với {{ BestSavingsRates::CHANGE_DAYS }} ngày trước</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php($change = $row['previous'] !== null ? round($row['best'] - $row['previous'], 2) : null)
                    <tr>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('compare.institutions.show', $row['institution']->slug) }}" class="flex items-center gap-2 font-semibold text-navy-900 hover:text-primary-700">
                                <span class="num w-5 text-right text-xs text-slate-400">{{ $loop->iteration }}</span>
                                @if ($logo = $row['institution']->logoUrl())<img src="{{ $logo }}" alt="" width="40" height="25" class="h-6 w-10 rounded object-contain" loading="lazy">@endif
                                {{ $topName($row) }}
                            </a>
                        </td>
                        @foreach (['online', 'counter'] as $channel)
                            <td @class(['num text-right whitespace-nowrap', 'font-bold text-navy-900' => $row[$channel] !== null && $row[$channel] === $row['best']])>
                                {!! $row[$channel] === null ? '<span class="text-slate-400">—</span>' : e(Format::percent($row[$channel])) !!}
                            </td>
                        @endforeach
                        <td class="num text-right whitespace-nowrap">{{ Format::money(SavingsInterest::simple($sampleAmount, $row['best'], $months)) }}</td>
                        <td class="num text-right whitespace-nowrap">
                            @if ($change === null || $change == 0)
                                <span class="text-slate-400">{{ $change === null ? '—' : 'Không đổi' }}</span>
                            @elseif ($change > 0)
                                <span class="text-income">▲ {{ Format::decimal($change) }} điểm</span>
                            @else
                                <span class="text-expense">▼ {{ Format::decimal(abs($change)) }} điểm</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
        <p class="mt-3 text-xs text-slate-500">Đơn vị %/năm. "—": ngân hàng chưa công bố kênh này. "Điểm" là điểm phần trăm. Lãi suất do ngân hàng quyết định và có thể thay đổi; xem nguồn và ngày xác minh ở trang từng ngân hàng và <a href="{{ route('legal.methodology') }}" class="underline">Phương pháp đánh giá</a>.</p>

        <div class="mt-5 flex flex-wrap gap-3">
            <x-ui.button :href="route('compare.savings.index', ['ky_han' => $months])" icon="calculator">Tính tiền lãi theo số tiền của bạn</x-ui.button>
            <x-ui.button :href="route('compare.savings.table')" variant="outline">Xem bảng mọi kỳ hạn</x-ui.button>
        </div>

        @if (\Laravel\Pennant\Feature::active('newsletter'))
            @include('newsletter::signup', ['term' => $months])
        @endif

        <x-ui.faq class="mt-10" :items="$faq" />

        @if ($otherTerms)
            <nav class="mt-10" aria-labelledby="other-terms">
                <h2 id="other-terms" class="text-lg font-bold text-navy-900">Lãi suất cao nhất kỳ hạn khác</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($otherTerms as $term)
                        <li><a href="{{ route('compare.savings.term', $term) }}" class="inline-block rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-sm font-medium text-navy-900 hover:border-primary-300 hover:bg-primary-50">{{ SavingsRate::termLabel($term) }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>
</x-layouts.public>
