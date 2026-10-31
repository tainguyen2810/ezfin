@php
    use App\Modules\Compare\Enums\SavingsChannel;
    use App\Modules\Compare\Models\SavingsRate;

    $name = $institution->short_name ?: $institution->name;
    $logo = $institution->logoUrl();
    $terms = collect($rates)->flatMap(fn ($byTerm) => array_keys(array_filter($byTerm, fn ($rate) => $rate !== null)))->unique()->sort()->values();
    $best12 = collect($rates)->map(fn ($byTerm) => $byTerm[12] ?? null)->filter()->max();
    $hasHistory = count($history['dates']) > 1;
    $dateLabels = array_map(fn ($date) => Format::date($date), $history['dates']);
@endphp
<x-layouts.public :title="'Lãi suất '.$name.' hôm nay'"
    :description="'Lãi suất tiết kiệm '.$name.' theo kỳ hạn'.($best12 ? ', 12 tháng cao nhất '.Format::rate($best12) : '').'; lịch sử lãi suất, thẻ tín dụng và tài khoản thanh toán của '.$name.'. Dữ liệu có nguồn và ngày xác minh.'"
    :canonical="route('compare.institutions.show', $institution->slug)">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Ngân hàng' => route('compare.institutions.index'), $name => null]" />

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-card border border-slate-200 bg-white">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $name }}" width="96" height="56" class="max-h-14 max-w-24 object-contain">
                @else
                    <span class="text-sm font-semibold text-slate-500">{{ $name }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold sm:text-3xl">Lãi suất {{ $name }}</h1>
                <p class="mt-1 text-slate-600">{{ $institution->name }}</p>
                @if ($institution->website)
                    <a href="{{ $institution->website }}" rel="nofollow noopener" target="_blank" class="mt-1 inline-flex items-center gap-1 text-sm text-primary-700 hover:underline">
                        Website chính thức <x-ui.icon name="arrow-top-right-on-square" class="size-4" />
                    </a>
                @endif
            </div>
            @if ($best12)
                <div class="rounded-card bg-navy-900 px-5 py-4 text-white">
                    <p class="text-xs text-white/70">Cao nhất kỳ hạn 12 tháng</p>
                    <p class="num mt-1 text-2xl font-bold">{{ Format::rate($best12) }}</p>
                </div>
            @endif
        </div>

        <div class="mt-8 space-y-8">
            @if ($rates)
                <section aria-labelledby="lai-suat">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h2 id="lai-suat" class="text-lg font-semibold">Lãi suất tiết kiệm theo kỳ hạn</h2>
                        <a href="{{ route('compare.savings.table') }}" class="text-sm font-medium text-primary-700 hover:underline">So với các ngân hàng khác</a>
                    </div>
                    <x-ui.table class="mt-3">
                        <caption class="sr-only">Lãi suất tiết kiệm {{ $name }}, %/năm</caption>
                        <thead>
                            <tr>
                                <th scope="col">Kỳ hạn</th>
                                @foreach (array_keys($rates) as $slug)<th scope="col" class="text-right">{{ SavingsChannel::fromSlug($slug)->getLabel() }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($terms as $term)
                                <tr>
                                    <td class="font-medium text-navy-900">{{ SavingsRate::termLabel($term) }}</td>
                                    @foreach ($rates as $byTerm)
                                        <td class="num text-right">{{ ($byTerm[$term] ?? null) === null ? '—' : Format::percent($byTerm[$term]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                    <p class="mt-2 text-xs text-slate-500">Mức cao nhất trong các gói tiết kiệm của {{ $name }} đang hiệu lực, ở mức tiền gửi thấp nhất. Lãi suất do ngân hàng quyết định và có thể thay đổi.</p>
                </section>
            @endif

            @if ($hasHistory)
                <section aria-labelledby="lich-su">
                    <h2 id="lich-su" class="text-lg font-semibold">Lãi suất thay đổi thế nào</h2>
                    <p class="mt-1 text-sm text-slate-600">Mức cao nhất kênh {{ mb_strtolower($history['channel']->getLabel()) }} tại mỗi lần {{ $name }} đổi biểu lãi suất.</p>
                    <x-ui.line-chart class="mt-3" :labels="$dateLabels" :caption="'Lịch sử lãi suất tiết kiệm '.$name"
                        :series="collect($history['series'])->map(fn ($values, $term) => ['label' => $term.' tháng', 'tone' => $term === 12 ? 'primary' : 'ink', 'values' => $values])->values()->all()" />
                    <details class="mt-2 text-sm">
                        <summary class="cursor-pointer font-medium text-primary-700">Xem bảng số liệu</summary>
                        <x-ui.table class="mt-2">
                            <thead><tr><th scope="col">Ngày áp dụng</th>@foreach (array_keys($history['series']) as $term)<th scope="col" class="text-right">{{ $term }} tháng</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach ($dateLabels as $i => $label)
                                    <tr><td>{{ $label }}</td>@foreach ($history['series'] as $values)<td class="num text-right">{{ $values[$i] === null ? '—' : Format::percent($values[$i]) }}</td>@endforeach</tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    </details>
                </section>
            @endif

            @if ($cards->isNotEmpty())
                <section aria-labelledby="the">
                    <h2 id="the" class="text-lg font-semibold">Thẻ tín dụng {{ $name }}</h2>
                    <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($cards as $card)
                            <li>
                                <a href="{{ route('compare.credit-cards.show', $card->slug) }}" class="group flex items-center gap-3 rounded-card border border-slate-200 bg-white p-3 shadow-card hover:border-primary-300">
                                    @if ($image = $card->imageUrl())<img src="{{ $image }}" alt="" width="80" height="50" class="h-12 w-20 shrink-0 rounded-lg object-cover" loading="lazy">@endif
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-navy-900 group-hover:text-primary-700">{{ $card->name }}</span>
                                        <span class="num block text-xs text-slate-500">Phí năm {{ (float) $card->creditCard->annual_fee > 0 ? Format::money($card->creditCard->annual_fee) : 'miễn phí' }}@if ($card->creditCard->interest_rate) · lãi {{ Format::rate($card->creditCard->interest_rate) }}@endif</span>
                                    </span>
                                    <x-ui.icon name="chevron-right" class="size-5 shrink-0 text-slate-400" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($accounts->isNotEmpty())
                <section aria-labelledby="tai-khoan">
                    <h2 id="tai-khoan" class="text-lg font-semibold">Tài khoản thanh toán {{ $name }}</h2>
                    <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($accounts as $account)
                            <li>
                                <a href="{{ route('compare.bank-accounts.show', $account->slug) }}" class="group flex items-center gap-3 rounded-card border border-slate-200 bg-white p-3 shadow-card hover:border-primary-300">
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-navy-900 group-hover:text-primary-700">{{ $account->name }}</span>
                                        <span class="num block text-xs text-slate-500">Phí quản lý {{ (float) $account->bankAccount->monthly_fee > 0 ? Format::money($account->bankAccount->monthly_fee).'/tháng' : 'miễn phí' }}</span>
                                    </span>
                                    <x-ui.icon name="chevron-right" class="size-5 shrink-0 text-slate-400" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-layouts.public>
