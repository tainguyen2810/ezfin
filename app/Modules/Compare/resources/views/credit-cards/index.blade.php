<x-layouts.public title="So sánh thẻ tín dụng"
    description="So sánh phí thường niên, hoàn tiền, lãi suất và điều kiện thu nhập của thẻ tín dụng tại Việt Nam. Dữ liệu có nguồn và ngày xác minh."
    :noindex="$filters->activeCount() > 0">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Thẻ tín dụng' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">So sánh thẻ tín dụng</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Lọc theo ngân hàng, phí và nhu cầu chi tiêu, chọn tối đa 3 thẻ để so sánh chi tiết. Thông tin chỉ để tham khảo; điều kiện cấp thẻ và hạn mức do ngân hàng quyết định.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <x-ui.filter-panel :action="route('compare.credit-cards.index')" :active-count="$filters->activeCount()">
                <x-ui.select name="ngan_hang" label="Ngân hàng" placeholder="Tất cả ngân hàng" :options="$institutions" :value="$filters->institution" />
                <x-ui.select name="phi" label="Phí thường niên" placeholder="Mọi mức phí" :options="$fees" :value="$filters->fee" />
                <x-ui.select name="nhu_cau" label="Nhu cầu sử dụng" placeholder="Mọi nhu cầu" :options="$needs" :value="$filters->need?->slug()" />
                <x-ui.money-input name="thu_nhap" label="Thu nhập hằng tháng của bạn" :value="$filters->income"
                    hint="Chỉ dùng để lọc, không lưu lại. Chỉ hiện thẻ đã công bố mức thu nhập tối thiểu." />
                <x-ui.select name="sap_xep" label="Sắp xếp theo" :options="\App\Modules\Compare\Support\CreditCardFilters::SORTS" :value="$filters->sort" />
            </x-ui.filter-panel>

            <div class="space-y-4">
                <h2 class="sr-only">Kết quả so sánh</h2>
                @php $partnerLinks = app(\App\Modules\Partner\Actions\PartnerLinks::class)->forProducts($cards->pluck('id')) @endphp
                <p class="text-sm text-slate-500" role="status">
                    @if ($cards->total() > 0)
                        <strong class="text-navy-900">{{ $cards->total() }}</strong> thẻ phù hợp · Sắp xếp theo: {{ mb_strtolower(\App\Modules\Compare\Support\CreditCardFilters::SORTS[$filters->sort]) }}
                    @endif
                </p>

                @if ($partnerLinks->isNotEmpty())<x-ui.disclosure />@endif
                @forelse ($cards as $card)
                    @php
                        $detail = $card->creditCard;
                        $fee = $detail?->annual_fee === null ? '—' : ((float) $detail->annual_fee === 0.0 ? 'Miễn phí' : Format::money($detail->annual_fee));
                        $highlights = collect($detail?->benefits ?? [])
                            ->map(fn ($benefit) => \App\Modules\Compare\Enums\CardBenefit::tryFrom($benefit)?->getLabel())
                            ->filter()->take(3)
                            ->when($detail?->annual_fee_note, fn ($items) => $items->prepend($detail->annual_fee_note))
                            ->all();
                    @endphp
                    <x-ui.product-card :id="$card->slug" :name="$card->name" :institution="$card->institution->short_name"
                        :logo="$card->institution->logoUrl()" :image="$card->imageUrl()" :highlights="$highlights"
                        :detail-url="route('compare.credit-cards.show', $card->slug)" :partner-url="$partnerLinks->get($card->id)?->url()" :partner-label="$partnerLinks->get($card->id)?->cta_label ?? ''"
                        :facts="[
                            'Phí thường niên' => $fee,
                            'Thu nhập tối thiểu' => Format::money($detail?->min_monthly_income).($detail?->min_monthly_income !== null ? '/tháng' : ''),
                            'Hoàn tiền tối đa' => Format::percent($detail?->cashback_rate),
                            'Lãi suất' => Format::rate($detail?->interest_rate),
                            'Miễn lãi tối đa' => $detail?->interest_free_days ? $detail->interest_free_days.' ngày' : '—',
                            'Tổ chức thẻ' => $detail?->network?->getLabel() ?? '—',
                        ]">
                        <x-slot:footer>
                            <x-ui.data-freshness :verified-at="$card->verified_at" source="Trang chính thức của ngân hàng"
                                :source-url="$card->source_url" :stale="$card->isDueForReview()" />
                        </x-slot:footer>
                    </x-ui.product-card>
                @empty
                    <x-ui.card>
                        <x-ui.empty-state icon="credit-card" title="Chưa có thẻ phù hợp"
                            :description="$filters->activeCount() > 0 ? 'Thử bỏ bớt điều kiện lọc để xem thêm thẻ.' : 'Dữ liệu thẻ tín dụng đang được cập nhật và xác minh.'">
                            @if ($filters->activeCount() > 0)
                                <x-ui.button :href="route('compare.credit-cards.index')" variant="outline">Xóa bộ lọc</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    </x-ui.card>
                @endforelse

                {{ $cards->links('pagination::tailwind') }}

                <p class="text-xs text-slate-500">
                    Thứ tự chỉ dựa trên tiêu chí bạn chọn. Thông số chưa được ngân hàng công bố hiển thị là "—" và xếp cuối.
                    Đây không phải khuyến nghị tài chính cá nhân.
                </p>
            </div>
        </div>
    </div>

    <x-ui.compare-tray :compare-url="route('compare.credit-cards.compare')" />
</x-layouts.public>
