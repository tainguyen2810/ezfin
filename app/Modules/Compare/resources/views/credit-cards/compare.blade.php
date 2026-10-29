<x-layouts.public :title="$cards->count() >= 2 ? 'So sánh '.$cards->pluck('name')->implode(' và ') : 'So sánh thẻ tín dụng'"
    description="So sánh chi tiết phí, hoàn tiền, lãi suất và điều kiện của các thẻ tín dụng bạn chọn." noindex>
    {{-- Đồng bộ khay so sánh với URL (mở link chia sẻ, bỏ thẻ khỏi bảng). --}}
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6"
        x-data x-init="$store.compare.replace(@js($cards->map(fn ($card) => ['id' => $card->slug, 'name' => $card->name])->values()))">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Thẻ tín dụng' => route('compare.credit-cards.index'), 'So sánh' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">So sánh thẻ tín dụng</h1>

        @if ($cards->count() < 2)
            <x-ui.card class="mt-6">
                <x-ui.empty-state icon="scale" title="Chọn ít nhất 2 thẻ để so sánh"
                    :description="'Đánh dấu ô “So sánh” trên tối đa '.$max.' thẻ trong danh sách, rồi bấm nút So sánh ở cuối màn hình.'">
                    <x-ui.button :href="route('compare.credit-cards.index')">Xem danh sách thẻ</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <p class="mt-1 text-slate-600">
                Ô có nhãn xanh là giá trị tốt nhất theo con số đã công bố (phí thấp hơn, hoàn tiền cao hơn), không phải đánh giá tổng thể.
            </p>

            <x-ui.compare-table class="mt-6" :products="$products" :rows="$rows" />

            <div class="mt-4 flex flex-wrap gap-2">
                @if ($cards->count() < $max)
                    <x-ui.button :href="route('compare.credit-cards.index')" variant="outline" icon="plus">Thêm thẻ để so sánh</x-ui.button>
                @else
                    <x-ui.button :href="route('compare.credit-cards.index')" variant="outline" icon="chevron-left">Quay lại danh sách thẻ</x-ui.button>
                @endif
            </div>

            <section class="mt-6 space-y-2" aria-labelledby="nguon-du-lieu">
                <h2 id="nguon-du-lieu" class="text-sm font-semibold text-navy-900">Nguồn dữ liệu</h2>
                @foreach ($cards as $card)
                    <div class="text-xs">
                        <p class="font-medium text-slate-700">{{ $card->name }}</p>
                        <x-ui.data-freshness :verified-at="$card->verified_at" source="Trang chính thức của ngân hàng"
                            :source-url="$card->source_url" :stale="$card->isDueForReview()" />
                    </div>
                @endforeach
                <p class="pt-2 text-xs text-slate-500">
                    Thông số chưa được ngân hàng công bố hiển thị là "—". Điều kiện cấp thẻ, hạn mức và ưu đãi do ngân hàng quyết định.
                    Đây không phải khuyến nghị tài chính cá nhân.
                </p>
            </section>
        @endif
    </div>
</x-layouts.public>
