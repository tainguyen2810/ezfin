@php
    use App\Modules\Compare\Support\LoanFacts;

    $logo = $loan->institution->logoUrl();
    $detail = $loan->loan;
    $partnerLinks = app(\App\Modules\Partner\Actions\PartnerLinks::class)->forProducts($similar->pluck('id')->push($loan->id));
    $kindUrl = route('compare.loans.index', ['loai' => $detail->kind->slug()]);
@endphp
<x-layouts.public :title="$loan->name.' – '.$loan->institution->short_name"
    :description="$loan->summary ?: 'Lãi suất, hạn mức, thời hạn và phí của '.$loan->name.' tại '.$loan->institution->short_name.'. Dữ liệu có nguồn và ngày xác minh.'"
    :canonical="route('compare.loans.show', $loan->slug)" :seo="$loan->seoMeta">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Vay' => route('compare.loans.index'), $detail->kind->getLabel() => $kindUrl, $loan->name => null]" />

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-card border border-slate-200 bg-white">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $loan->institution->short_name }}" width="96" height="56" class="max-h-14 max-w-24 object-contain">
                @else
                    <span class="text-sm font-semibold text-slate-500">{{ $loan->institution->short_name }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $loan->name }}</h1>
                <p class="mt-1 text-slate-600"><a href="{{ route('compare.institutions.show', $loan->institution->slug) }}" class="underline decoration-slate-300 underline-offset-4 hover:text-navy-900">{{ $loan->institution->name }}</a></p>
                <div class="mt-3 flex flex-wrap gap-1.5"><x-ui.badge variant="primary">{{ $detail->kind->getLabel() }}</x-ui.badge></div>
                @if ($partnerLink = $partnerLinks->get($loan->id))
                    <div class="mt-4">
                        <x-ui.button :href="$partnerLink->url()" icon="arrow-top-right-on-square" rel="sponsored nofollow noopener" target="_blank">{{ $partnerLink->cta_label }}</x-ui.button>
                    </div>
                @endif
            </div>
        </div>

        @if ($partnerLinks->isNotEmpty())<x-ui.disclosure class="mt-4" />@endif

        @if ($loan->summary)
            <p class="mt-6 max-w-3xl text-slate-700">{{ $loan->summary }}</p>
        @endif

        <section class="mt-8" aria-labelledby="lai-suat-phi">
            <h2 id="lai-suat-phi" class="text-lg font-semibold">Lãi suất, hạn mức và phí</h2>
            <x-ui.card class="mt-3">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-3">
                    @foreach ($facts as $label => $value)
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="num mt-0.5 text-sm font-semibold text-navy-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($detail->prepayment_note)
                    <p class="mt-4 text-sm text-slate-600">Trả nợ trước hạn: {{ $detail->prepayment_note }}</p>
                @endif
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    @if ($detail->isFloating())
                        Lãi ưu đãi chỉ áp dụng {{ $detail->promo_months }} tháng đầu; sau đó lãi thả nổi và thường cao hơn đáng kể. Hãy tính khả năng trả nợ theo mức lãi sau ưu đãi.
                    @endif
                    "—" là thông số ngân hàng chưa công bố.
                </p>
            </x-ui.card>
        </section>

        @if (Route::has('planner.tools.show') && \Laravel\Pennant\Feature::active('planner') && $detail->promo_rate !== null)
            <p class="mt-4 text-sm">
                <a href="{{ route('planner.tools.show', ['khoan-vay', 'lai_suat' => (float) $detail->promo_rate]) }}" class="font-semibold text-primary-700 underline underline-offset-4">Tính tiền trả hằng tháng với lãi {{ Format::rate($detail->promo_rate) }}</a>
            </p>
        @endif

        @if ($loan->pros || $loan->cons)
            <section class="mt-8 grid gap-4 sm:grid-cols-2" aria-label="Ưu điểm và hạn chế">
                @foreach (['Ưu điểm' => [$loan->pros, 'check-circle', 'text-green-700'], 'Hạn chế' => [$loan->cons, 'minus-circle', 'text-amber-700']] as $heading => [$items, $icon, $color])
                    @if ($items)
                        <x-ui.card>
                            <h2 class="text-base font-semibold">{{ $heading }}</h2>
                            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                @foreach ($items as $item)
                                    <li class="flex gap-2"><x-ui.icon :name="$icon" class="mt-0.5 size-4 shrink-0 {{ $color }}" />{{ $item }}</li>
                                @endforeach
                            </ul>
                        </x-ui.card>
                    @endif
                @endforeach
            </section>
        @endif

        <section class="mt-8" aria-labelledby="nguon">
            <h2 id="nguon" class="text-lg font-semibold">Nguồn dữ liệu</h2>
            <x-ui.data-freshness class="mt-2" :verified-at="$loan->verified_at" source="Biểu lãi suất cho vay chính thức của ngân hàng"
                :source-url="$loan->source_url" :stale="$loan->isDueForReview()" />
            @if ($loan->source_note)
                <p class="mt-1 text-xs text-slate-500">{{ $loan->source_note }}</p>
            @endif
            <p class="mt-3 text-xs text-slate-500">
                Thông tin chỉ để tham khảo. Lãi suất, hạn mức và điều kiện vay do ngân hàng quyết định sau khi thẩm định hồ sơ và có thể thay đổi.
                Đây không phải khuyến nghị tài chính cá nhân.
            </p>
        </section>

        @if ($similar->isNotEmpty())
            <section class="mt-10" aria-labelledby="vay-tuong-tu">
                <h2 id="vay-tuong-tu" class="text-lg font-semibold">Khoản vay tương tự</h2>
                <p class="mt-1 text-sm text-slate-500">Cùng loại vay, lãi ưu đãi gần nhất.</p>
                <div class="mt-4 space-y-4">
                    @foreach ($similar as $other)
                        <x-ui.product-card :id="$other->slug" :name="$other->name" :institution="$other->institution->short_name"
                            :logo="$other->institution->logoUrl()" :detail-url="route('compare.loans.show', $other->slug)" :comparable="false"
                            :partner-url="$partnerLinks->get($other->id)?->url()" :partner-label="$partnerLinks->get($other->id)?->cta_label ?? ''"
                            :facts="array_slice(LoanFacts::for($other), 0, 3, true)" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
