@php
    use App\Modules\Compare\Enums\LoanKind;
    use App\Modules\Compare\Support\LoanFacts;
    use App\Modules\Compare\Support\LoanFilters;

    $heading = match ($filters->kind) {
        LoanKind::Personal => 'So sánh lãi suất vay tiêu dùng tín chấp',
        LoanKind::Home => 'So sánh lãi suất vay mua nhà',
        LoanKind::Auto => 'So sánh lãi suất vay mua ô tô',
        default => 'So sánh lãi suất vay ngân hàng',
    };
    $intro = match ($filters->kind) {
        LoanKind::Personal => 'Vay không cần tài sản bảo đảm, xét duyệt theo thu nhập. Lãi thường cao hơn vay thế chấp; chú ý phí hồ sơ và phí trả nợ trước hạn.',
        LoanKind::Home => 'Vay thế chấp bằng chính căn nhà, thời hạn tới vài chục năm. Lãi ưu đãi chỉ áp dụng vài năm đầu, sau đó thả nổi theo lãi cơ sở cộng biên độ.',
        LoanKind::Auto => 'Vay thế chấp bằng chiếc xe mua, thời hạn thường đến 7–8 năm. So sánh cả lãi sau ưu đãi và tỷ lệ cho vay trên giá xe.',
        default => 'So sánh lãi ưu đãi, lãi sau ưu đãi, hạn mức và thời hạn của khoản vay tiêu dùng, mua nhà, mua ô tô.',
    };
    $partnerLinks = app(\App\Modules\Partner\Actions\PartnerLinks::class)->forProducts($loans->pluck('id'));
    $calculator = $filters->canEstimate() && $filters->term % 12 === 0 && Route::has('planner.tools.show') && \Laravel\Pennant\Feature::active('planner');
@endphp
<x-layouts.public :title="$heading"
    :description="$intro.' Dữ liệu có nguồn và ngày xác minh.'"
    :canonical="route('compare.loans.index', $filters->kind ? ['loai' => $filters->kind->slug()] : [])"
    :noindex="! $filters->isIndexable() || $loans->currentPage() > 1">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="$filters->kind
            ? ['Trang chủ' => route('home'), 'Vay' => route('compare.loans.index'), $filters->kind->getLabel() => null]
            : ['Trang chủ' => route('home'), 'Vay' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">{{ $heading }}</h1>
        <p class="mt-1 max-w-3xl text-slate-600">{{ $intro }} Điều kiện vay và lãi suất thực tế do ngân hàng quyết định sau khi thẩm định.</p>

        <nav class="mt-4 flex flex-wrap gap-2" aria-label="Loại vay">
            @foreach (['' => 'Tất cả'] + $kinds->all() as $slug => $label)
                @php $active = ($filters->kind?->slug() ?? '') === $slug @endphp
                <a href="{{ route('compare.loans.index', $slug ? ['loai' => $slug] : []) }}" @if ($active) aria-current="page" @endif
                    @class(['rounded-full border px-3 py-1.5 text-sm font-medium', 'border-navy-900 bg-navy-900 text-white' => $active, 'border-slate-300 bg-white text-slate-700 hover:border-primary-600' => ! $active])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <x-ui.filter-panel :action="route('compare.loans.index')" :active-count="$filters->activeCount()">
                <x-ui.select name="loai" label="Loại vay" placeholder="Mọi loại vay" :options="$kinds" :value="$filters->kind?->slug()" />
                <x-ui.money-input name="so_tien" label="Số tiền cần vay" :value="$filters->amount" />
                <x-ui.select name="thoi_han" label="Thời hạn vay" placeholder="Chọn thời hạn" :options="LoanFilters::termOptions()" :value="$filters->term"
                    hint="Nhập số tiền và thời hạn để ước tính tiền trả tháng đầu. Không lưu lại." />
                <x-ui.select name="ngan_hang" label="Ngân hàng" placeholder="Tất cả ngân hàng" :options="$institutions" :value="$filters->institution" />
                <x-ui.select name="sap_xep" label="Sắp xếp theo" :options="LoanFilters::SORTS" :value="$filters->sort" />
            </x-ui.filter-panel>

            <div class="space-y-4">
                <h2 class="sr-only">Kết quả so sánh</h2>
                <p class="text-sm text-slate-500" role="status">
                    @if ($loans->total() > 0)
                        <strong class="text-navy-900">{{ $loans->total() }}</strong> khoản vay phù hợp · Sắp xếp theo: {{ mb_strtolower(LoanFilters::SORTS[$filters->sort]) }}
                    @endif
                </p>

                @if ($partnerLinks->isNotEmpty())<x-ui.disclosure />@endif
                @forelse ($loans as $loan)
                    @php
                        $facts = LoanFacts::for($loan);
                        if ($filters->canEstimate() && ($payment = LoanFacts::firstPayment($loan, $filters->amount, $filters->term)) !== null) {
                            $facts = ['Trả tháng đầu (ước tính)' => Format::money($payment)] + array_slice($facts, 0, 3, true);
                        }
                        $highlights = array_values(array_filter([
                            $filters->kind ? null : $loan->loan->kind->getLabel(),
                            $loan->loan->max_ltv !== null ? 'Vay tới '.Format::percent($loan->loan->max_ltv, 0).' giá trị tài sản' : null,
                            $loan->loan->prepayment_fee !== null && (float) $loan->loan->prepayment_fee === 0.0 ? 'Miễn phí trả nợ trước hạn' : null,
                        ]));
                    @endphp
                    <x-ui.product-card :id="$loan->slug" :name="$loan->name" :institution="$loan->institution->short_name"
                        :logo="$loan->institution->logoUrl()" :highlights="$highlights" :facts="$facts" :comparable="false"
                        :detail-url="route('compare.loans.show', $loan->slug)" :partner-url="$partnerLinks->get($loan->id)?->url()" :partner-label="$partnerLinks->get($loan->id)?->cta_label ?? ''">
                        <x-slot:footer>
                            <x-ui.data-freshness :verified-at="$loan->verified_at" source="Biểu lãi suất cho vay chính thức của ngân hàng"
                                :source-url="$loan->source_url" :stale="$loan->isDueForReview()" />
                        </x-slot:footer>
                    </x-ui.product-card>
                @empty
                    <x-ui.card>
                        <x-ui.empty-state icon="home" title="Chưa có khoản vay phù hợp"
                            :description="$filters->activeCount() > 0 ? 'Thử bỏ bớt điều kiện lọc, giảm số tiền hoặc thời hạn vay.' : 'Dữ liệu khoản vay đang được cập nhật và xác minh.'">
                            @if ($filters->activeCount() > 0)
                                <x-ui.button :href="route('compare.loans.index', $filters->kind ? ['loai' => $filters->kind->slug()] : [])" variant="outline">Xóa bộ lọc</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    </x-ui.card>
                @endforelse

                {{ $loans->links('pagination::tailwind') }}

                @if ($filters->canEstimate() && $loans->total() > 0)
                    <x-ui.alert variant="info">
                        Tiền trả tháng đầu tính theo dư nợ giảm dần với lãi ưu đãi. Sau thời gian ưu đãi lãi thả nổi nên số tiền sẽ khác.
                        @if ($calculator)<a href="{{ route('planner.tools.show', ['khoan-vay', 'so_tien' => $filters->amount, 'so_nam' => intdiv($filters->term, 12)]) }}" class="font-semibold underline underline-offset-4">Xem lịch trả nợ chi tiết</a>@endif
                    </x-ui.alert>
                @endif

                <p class="text-xs text-slate-500">
                    Thứ tự chỉ dựa trên tiêu chí bạn chọn, không phụ thuộc quan hệ đối tác. Thông số chưa được ngân hàng công bố hiển thị là "—" và xếp cuối.
                    Tiền trả ước tính chưa gồm phí hồ sơ, bảo hiểm khoản vay. Đây không phải khuyến nghị tài chính cá nhân.
                </p>
            </div>
        </div>
    </div>
</x-layouts.public>
