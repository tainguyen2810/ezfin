<x-layouts.public title="So sánh tài khoản ngân hàng"
    description="So sánh phí quản lý, số dư tối thiểu, phí chuyển khoản và tính năng của tài khoản thanh toán tại Việt Nam. Dữ liệu có nguồn và ngày xác minh."
    :noindex="$filters->activeCount() > 0">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Tài khoản ngân hàng' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">So sánh tài khoản ngân hàng</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Lọc tài khoản thanh toán theo ngân hàng, phí quản lý và tính năng. Biểu phí chỉ để tham khảo; điều kiện mở tài khoản và ưu đãi do ngân hàng quyết định.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <x-ui.filter-panel :action="route('compare.bank-accounts.index')" :active-count="$filters->activeCount()">
                <x-ui.select name="ngan_hang" label="Ngân hàng" placeholder="Tất cả ngân hàng" :options="$institutions" :value="$filters->institution" />
                <x-ui.select name="phi" label="Phí quản lý tài khoản" placeholder="Mọi mức phí" :options="$fees" :value="$filters->fee" />
                <x-ui.select name="tinh_nang" label="Tính năng" placeholder="Mọi tính năng" :options="$features" :value="$filters->feature?->slug()" />
                <x-ui.select name="sap_xep" label="Sắp xếp theo" :options="\App\Modules\Compare\Support\BankAccountFilters::SORTS" :value="$filters->sort" />
            </x-ui.filter-panel>

            <div class="space-y-4">
                <p class="text-sm text-slate-500" role="status">
                    @if ($accounts->total() > 0)
                        <strong class="text-navy-900">{{ $accounts->total() }}</strong> tài khoản phù hợp · Sắp xếp theo: {{ mb_strtolower(\App\Modules\Compare\Support\BankAccountFilters::SORTS[$filters->sort]) }}
                    @endif
                </p>

                @forelse ($accounts as $account)
                    @php
                        $highlights = collect(\App\Modules\Compare\Support\BankAccountFacts::features($account))->take(3)
                            ->when($account->bankAccount?->monthly_fee_note, fn ($items) => $items->prepend($account->bankAccount->monthly_fee_note))
                            ->all();
                    @endphp
                    <x-ui.product-card :id="$account->slug" :name="$account->name" :institution="$account->institution->short_name"
                        :logo="$account->institution->logoUrl()" :highlights="$highlights"
                        :detail-url="route('compare.bank-accounts.show', $account->slug)" :comparable="false"
                        :facts="\App\Modules\Compare\Support\BankAccountFacts::for($account)">
                        <x-slot:footer>
                            <x-ui.data-freshness :verified-at="$account->verified_at" source="Biểu phí chính thức của ngân hàng"
                                :source-url="$account->source_url" :stale="$account->isDueForReview()" />
                        </x-slot:footer>
                    </x-ui.product-card>
                @empty
                    <x-ui.card>
                        <x-ui.empty-state icon="building-library" title="Chưa có tài khoản phù hợp"
                            :description="$filters->activeCount() > 0 ? 'Thử bỏ bớt điều kiện lọc để xem thêm tài khoản.' : 'Dữ liệu tài khoản ngân hàng đang được cập nhật và xác minh.'">
                            @if ($filters->activeCount() > 0)
                                <x-ui.button :href="route('compare.bank-accounts.index')" variant="outline">Xóa bộ lọc</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    </x-ui.card>
                @endforelse

                {{ $accounts->links('pagination::tailwind') }}

                <p class="text-xs text-slate-500">
                    Thứ tự chỉ dựa trên tiêu chí bạn chọn. Thông số chưa được ngân hàng công bố hiển thị là "—" và xếp cuối.
                    Phí chưa gồm VAT nếu biểu phí của ngân hàng không ghi khác. Đây không phải khuyến nghị tài chính cá nhân.
                </p>
            </div>
        </div>
    </div>
</x-layouts.public>
