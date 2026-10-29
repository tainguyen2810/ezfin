@php
    $detail = $card->creditCard;
    $benefits = collect($detail->benefits ?? [])->map(fn ($benefit) => \App\Modules\Compare\Enums\CardBenefit::tryFrom($benefit)?->getLabel())->filter();
    $image = $card->imageUrl() ?? $card->institution->logoUrl();
@endphp
<x-layouts.public :title="$card->name.' – '.$card->institution->short_name"
    :description="$card->summary ?: 'Phí, lãi suất, hoàn tiền và điều kiện mở '.$card->name.' của '.$card->institution->short_name.'. Dữ liệu có nguồn và ngày xác minh.'"
    :canonical="route('compare.credit-cards.show', $card->slug)">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Thẻ tín dụng' => route('compare.credit-cards.index'), $card->name => null]" />

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="flex h-24 w-36 shrink-0 items-center justify-center overflow-hidden rounded-card border border-slate-200 bg-white">
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $card->name }}" class="max-h-20 max-w-32 object-contain">
                @else
                    <span class="text-sm font-semibold text-slate-500">{{ $card->institution->short_name }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $card->name }}</h1>
                <p class="mt-1 text-slate-600">{{ $card->institution->name }}</p>
                @if ($benefits->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($benefits as $benefit)<x-ui.badge variant="primary">{{ $benefit }}</x-ui.badge>@endforeach
                    </div>
                @endif
                <label x-data class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-navy-900 hover:bg-slate-50">
                    <input type="checkbox" class="size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-600"
                        x-bind:checked="$store.compare.has(@js($card->slug))"
                        x-on:change="$store.compare.toggle({ id: @js($card->slug), name: @js($card->name) }); $el.checked = $store.compare.has(@js($card->slug))">
                    Thêm vào so sánh
                </label>
            </div>
        </div>

        @if ($card->summary)
            <p class="mt-6 max-w-3xl text-slate-700">{{ $card->summary }}</p>
        @endif

        <section class="mt-8" aria-labelledby="thong-so">
            <h2 id="thong-so" class="text-lg font-semibold">Thông số chính</h2>
            <x-ui.card class="mt-3">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-3">
                    @foreach ($facts as $label => $value)
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="num mt-0.5 text-sm font-semibold text-navy-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">"—" là thông số ngân hàng chưa công bố.</p>
            </x-ui.card>
        </section>

        @if ($card->pros || $card->cons)
            <section class="mt-8 grid gap-4 sm:grid-cols-2" aria-label="Ưu điểm và hạn chế">
                @foreach (['Ưu điểm' => [$card->pros, 'check-circle', 'text-green-700'], 'Hạn chế' => [$card->cons, 'minus-circle', 'text-amber-700']] as $heading => [$items, $icon, $color])
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
            <x-ui.data-freshness class="mt-2" :verified-at="$card->verified_at" source="Trang chính thức của ngân hàng"
                :source-url="$card->source_url" :stale="$card->isDueForReview()" />
            @if ($card->source_note)
                <p class="mt-1 text-xs text-slate-500">{{ $card->source_note }}</p>
            @endif
            <p class="mt-3 text-xs text-slate-500">
                Thông tin chỉ để tham khảo. Điều kiện cấp thẻ, hạn mức và ưu đãi do ngân hàng quyết định và có thể thay đổi.
                Đây không phải khuyến nghị tài chính cá nhân.
            </p>
        </section>

        @if ($similar->isNotEmpty())
            <section class="mt-10" aria-labelledby="the-tuong-tu">
                <h2 id="the-tuong-tu" class="text-lg font-semibold">Thẻ tương tự</h2>
                <p class="mt-1 text-sm text-slate-500">Cùng nhóm lợi ích hoặc mức phí thường niên gần nhất.</p>
                <div class="mt-4 space-y-4">
                    @foreach ($similar as $other)
                        @php $otherDetail = $other->creditCard; @endphp
                        <x-ui.product-card :id="$other->slug" :name="$other->name" :institution="$other->institution->short_name"
                            :logo="$other->institution->logoUrl()" :detail-url="route('compare.credit-cards.show', $other->slug)"
                            :facts="[
                                'Phí thường niên' => $otherDetail->annual_fee === null ? '—' : ((float) $otherDetail->annual_fee === 0.0 ? 'Miễn phí' : Format::money($otherDetail->annual_fee)),
                                'Thu nhập tối thiểu' => $otherDetail->min_monthly_income === null ? '—' : Format::money($otherDetail->min_monthly_income).'/tháng',
                                'Hoàn tiền tối đa' => Format::percent($otherDetail->cashback_rate),
                            ]" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-ui.compare-tray :compare-url="route('compare.credit-cards.compare')" />
</x-layouts.public>
