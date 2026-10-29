@php
    $paymentLabel = $filters->payment ? 'lĩnh lãi '.mb_strtolower($filters->payment->getLabel()) : 'mọi hình thức lĩnh lãi';
    $leader = $results->count() > 1 && $results[0]['interest'] > $results[1]['interest'];
    $partnerLinks = app(\App\Modules\Partner\Actions\PartnerLinks::class)->forProducts($results->map(fn ($result) => $result['rate']->product_id));
@endphp
<x-layouts.public title="So sánh lãi suất tiết kiệm"
    description="So sánh lãi suất gửi tiết kiệm các ngân hàng theo số tiền và kỳ hạn, xếp theo tiền lãi nhận được. Dữ liệu có nguồn và ngày xác minh."
    :noindex="! $filters->isDefault()" :canonical="$filters->isDefault() ? route('compare.savings.index') : null">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Lãi suất tiết kiệm' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">So sánh lãi suất tiết kiệm</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Nhập số tiền và kỳ hạn để xem ngân hàng nào trả nhiều tiền lãi nhất theo biểu lãi suất đã công bố.</p>

        <x-ui.card class="mt-6">
            <form method="GET" action="{{ route('compare.savings.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_auto] lg:items-end">
                <x-ui.money-input name="so_tien" label="Số tiền gửi" :value="$filters->amount" required />
                <x-ui.select name="ky_han" label="Kỳ hạn" :options="$terms" :value="$filters->term" />
                <x-ui.select name="kenh" label="Kênh gửi" placeholder="Mọi kênh" :options="$channels" :value="$filters->channel?->slug()" />
                <x-ui.select name="linh_lai" label="Lĩnh lãi" :options="$payments"
                    :value="$filters->payment?->slug() ?? \App\Modules\Compare\Support\SavingsFilters::ANY_PAYMENT" />
                <x-ui.button type="submit" class="sm:col-span-2 lg:col-span-1">Tính lãi</x-ui.button>
            </form>
        </x-ui.card>

        <div class="mt-6 space-y-4">
            <h2 class="sr-only">Kết quả so sánh</h2>
            <p class="text-sm text-slate-500" role="status">
                Gửi <strong class="text-navy-900">{{ Format::money($filters->amount) }}</strong>,
                kỳ hạn <strong class="text-navy-900">{{ \App\Modules\Compare\Models\SavingsRate::termLabel($filters->term) }}</strong>,
                {{ $paymentLabel }}{{ $filters->channel ? ', kênh '.mb_strtolower($filters->channel->getLabel()) : '' }}:
                <strong class="text-navy-900">{{ $results->count() }}</strong> sản phẩm, xếp theo tiền lãi nhận được.
            </p>

            @if ($partnerLinks->isNotEmpty())<x-ui.disclosure />@endif

            @forelse ($results as $index => $result)
                @php
                    $rate = $result['rate'];
                    $product = $rate->product;
                    $tier = (float) $rate->min_amount > 0 || $rate->max_amount !== null
                        ? ((float) $rate->min_amount > 0 ? 'Từ '.Format::money($rate->min_amount) : '').($rate->max_amount !== null ? ((float) $rate->min_amount > 0 ? ' đến dưới ' : 'Dưới ').Format::money($rate->max_amount) : '')
                        : 'Mọi mức tiền';
                @endphp
                <x-ui.product-card :id="$product->slug" :name="($index + 1).'. '.$product->name" :institution="$product->institution->short_name"
                    :logo="$product->institution->logoUrl()" :comparable="false"
                    :partner-url="$partnerLinks->get($product->id)?->url()" :partner-label="$partnerLinks->get($product->id)?->cta_label ?? ''"
                    :highlights="$index === 0 && $leader ? ['Tiền lãi cao nhất'] : []"
                    :facts="[
                        'Tiền lãi' => Format::money($result['interest']),
                        'Lãi suất' => Format::rate($rate->rate),
                        'Tổng nhận cuối kỳ' => Format::money($result['total']),
                        'Kênh' => $rate->channel->getLabel(),
                        'Lĩnh lãi' => $rate->interest_payment->getLabel(),
                        'Mức tiền áp dụng' => $tier,
                    ]">
                    <x-slot:footer>
                        <x-ui.data-freshness :verified-at="$product->verified_at" source="Biểu lãi suất của ngân hàng"
                            :source-url="$product->source_url" :stale="$product->isDueForReview()" />
                        <p class="mt-1 text-xs text-slate-500">Lãi suất hiệu lực từ {{ Format::date($rate->effective_from) }}</p>
                    </x-slot:footer>
                </x-ui.product-card>
            @empty
                <x-ui.card>
                    <x-ui.empty-state icon="banknotes" title="Chưa có lãi suất phù hợp"
                        :description="$filters->isDefault() ? 'Dữ liệu lãi suất đang được cập nhật và xác minh.' : 'Thử kỳ hạn khác, hoặc chọn mọi kênh và mọi hình thức lĩnh lãi.'">
                        @unless ($filters->isDefault())
                            <x-ui.button :href="route('compare.savings.index')" variant="outline">Về mặc định</x-ui.button>
                        @endunless
                    </x-ui.empty-state>
                </x-ui.card>
            @endforelse

            <x-ui.alert title="Cách tính">
                Tiền lãi = số tiền × lãi suất (%/năm) × số tháng ÷ 12, làm tròn đến đồng. Ngân hàng có thể tính theo số ngày gửi thực tế
                nên số tiền nhận được có thể chênh lệch nhỏ. Lĩnh lãi hằng tháng, hằng quý hay đầu kỳ: con số là tổng tiền lãi trong kỳ, không tính gửi lại tiền lãi.
                Rút trước hạn thường chỉ được hưởng lãi không kỳ hạn.
            </x-ui.alert>
            <p class="text-xs text-slate-500">
                Thứ tự chỉ dựa trên tiền lãi tính theo biểu lãi suất đã công bố. Hãy xác nhận lại với ngân hàng trước khi gửi.
                Đây không phải khuyến nghị tài chính cá nhân.
            </p>
        </div>
    </div>
</x-layouts.public>
