<x-layouts.public title="Mẫu: So sánh thẻ tín dụng" :navigation="$navigation" noindex>
    @include('core::style-guide._banner')

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => '#', 'Sản phẩm tài chính' => '#', 'Thẻ tín dụng' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">So sánh thẻ tín dụng</h1>
        <p class="mt-1 text-slate-600">Tìm thẻ phù hợp với nhu cầu chi tiêu. Thông tin tham khảo, điều kiện cấp thẻ do ngân hàng quyết định.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <x-ui.filter-panel :action="url('/_ui/so-sanh')" :active-count="1">
                <x-ui.select name="nhu_cau" label="Nhu cầu sử dụng" placeholder="Tất cả" :options="['hoan-tien' => 'Hoàn tiền', 'du-lich' => 'Du lịch', 'mua-sam' => 'Mua sắm']" value="hoan-tien" />
                <x-ui.select name="phi" label="Phí thường niên" placeholder="Tất cả" :options="['mien-phi' => 'Miễn phí', 'duoi-500k' => 'Dưới 500.000 đ']" />
                <x-ui.money-input name="thu_nhap" label="Thu nhập hằng tháng của bạn" hint="Chỉ dùng để lọc, không lưu lại." />
            </x-ui.filter-panel>

            <div class="space-y-4">
                <x-ui.disclosure />
                <p class="text-sm text-slate-500">3 thẻ phù hợp · Sắp xếp theo: phí thường niên thấp nhất</p>
                @foreach ($cards as $card)
                    <x-ui.product-card :id="$card['id']" :name="$card['name']" :institution="$card['institution']"
                        :highlights="$card['highlights']" :facts="$card['facts']" :sponsored="$card['sponsored']"
                        detail-url="#" partner-url="#" />
                @endforeach
                <x-ui.data-freshness :verified-at="now()->subDays(2)" source="Website chính thức của từng ngân hàng" />
            </div>
        </div>
    </div>

    <x-ui.compare-tray :compare-url="url('/_ui/so-sanh')" />
</x-layouts.public>
