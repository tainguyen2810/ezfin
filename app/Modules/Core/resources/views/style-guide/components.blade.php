<x-layouts.public title="Mẫu giao diện" :navigation="$navigation" noindex>
    <x-slot:actions>
        <x-ui.button variant="ghost">Đăng nhập</x-ui.button>
        <x-ui.button>Bắt đầu miễn phí</x-ui.button>
    </x-slot:actions>

    @include('core::style-guide._banner')

    @php
        $section = 'scroll-mt-20 space-y-4';
        $h2 = 'text-lg font-semibold';
    @endphp

    <div class="mx-auto max-w-7xl space-y-12 px-4 py-8 sm:px-6">
        <header>
            <h1 class="text-2xl font-bold sm:text-3xl">Hệ thống giao diện EzFin</h1>
            <p class="mt-2 max-w-2xl text-slate-600">Component dùng chung và các trạng thái tương tác. Mọi màn hình website, ứng dụng và quản trị dựng từ các thành phần này.</p>
        </header>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Màu và chữ</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['Navy', 'bg-navy-900', '#0B1F3A'],
                    ['Xanh dương (chính)', 'bg-primary-600', 'blue-600'],
                    ['Thu / tăng', 'bg-income', 'green-600'],
                    ['Chi / vượt', 'bg-expense', 'red-600'],
                    ['Cảnh báo', 'bg-warning', 'amber-500'],
                    ['Nền', 'bg-slate-50 border border-slate-200', 'slate-50'],
                ] as [$name, $class, $code])
                    <div class="overflow-hidden rounded-card border border-slate-200 bg-white">
                        <div class="h-14 {{ $class }}"></div>
                        <div class="p-3 text-sm"><p class="font-medium text-navy-900">{{ $name }}</p><p class="text-xs text-slate-500">{{ $code }}</p></div>
                    </div>
                @endforeach
            </div>
            <x-ui.card>
                <p class="text-3xl font-bold text-navy-900">Tài chính thông minh cho cuộc sống tốt hơn</p>
                <p class="mt-2 text-xl font-semibold text-navy-900">Tiêu đề mục · Be Vietnam Pro 600</p>
                <p class="mt-2 text-slate-600">Đoạn văn thường: Lãi suất tiết kiệm kỳ hạn 12 tháng, lĩnh lãi cuối kỳ. Ạ ặ ẩ ễ ố ộ ữ ỹ đ Đ.</p>
                <p class="num mt-2 text-2xl font-bold text-navy-900">{{ Format::money(520000000) }} · {{ Format::rate(5.5) }} · {{ Format::date('2026-10-29') }}</p>
            </x-ui.card>
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Nút</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button>Chính</x-ui.button>
                <x-ui.button variant="secondary">Phụ</x-ui.button>
                <x-ui.button variant="outline">Viền</x-ui.button>
                <x-ui.button variant="ghost">Nhẹ</x-ui.button>
                <x-ui.button variant="danger" icon="trash">Xóa</x-ui.button>
                <x-ui.button icon="plus">Thêm giao dịch</x-ui.button>
                <x-ui.button loading>Đang lưu</x-ui.button>
                <x-ui.button disabled>Không khả dụng</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button size="sm">Nhỏ</x-ui.button>
                <x-ui.button>Vừa</x-ui.button>
                <x-ui.button size="lg">Lớn</x-ui.button>
            </div>
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Form</h2>
            <x-ui.card>
                <div class="grid gap-5 md:grid-cols-2">
                    <x-ui.money-input name="so_tien" label="Số tiền" value="15000000" hint="Gõ số, hệ thống tự thêm dấu chấm." required />
                    <x-ui.select name="ky_han" label="Kỳ hạn" :options="['1' => '1 tháng', '6' => '6 tháng', '12' => '12 tháng']" value="12" />
                    <x-ui.input name="ghi_chu" label="Ghi chú" placeholder="Ví dụ: lương tháng 10" />
                    <x-ui.input name="ngay" label="Ngày giao dịch" type="date" value="2026-10-29" />
                    <x-ui.input name="email" label="Email (trạng thái lỗi)" value="ten@" />
                    <x-ui.input name="khoa" label="Ô bị khóa" value="Không sửa được" disabled />
                    <x-ui.share-link class="md:col-span-2" url="https://ezfin.vn/cong-cu/lai-kep?so_tien=50000000&so_nam=5" label="Liên kết chia sẻ (nút Sao chép chỉ hiện khi bật JS)" />
                    <div class="md:col-span-2"><x-ui.toggle name="nhan_tin" label="Nhận email ưu đãi" description="Bạn có thể tắt bất cứ lúc nào trong Cài đặt." /></div>
                </div>
            </x-ui.card>
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Nhãn và dữ liệu sản phẩm</h2>
            <div class="flex flex-wrap gap-2">
                <x-ui.badge>Trung tính</x-ui.badge>
                <x-ui.badge variant="primary">Hoàn tiền đến 10%</x-ui.badge>
                <x-ui.badge variant="success" icon="check">Đang áp dụng</x-ui.badge>
                <x-ui.badge variant="warning">Sắp hết hạn</x-ui.badge>
                <x-ui.badge variant="danger">Vượt ngân sách</x-ui.badge>
                <x-ui.badge variant="sponsored">Tài trợ</x-ui.badge>
            </div>
            <x-ui.data-freshness :verified-at="now()->subDays(3)" source="Website chính thức của ngân hàng" source-url="#" />
            <x-ui.data-freshness :verified-at="now()->subDays(45)" source="Biểu phí công bố" />
            <x-ui.disclosure />
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Thẻ số liệu và tiến độ</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat-card label="Tổng tài sản" :value="Format::money(520000000)" :compact-value="Format::compactMoney(520000000)" change="5% so với tháng trước" trend="up" icon="wallet" />
                <x-ui.stat-card label="Tổng nợ" :value="Format::money(251500000)" :compact-value="Format::compactMoney(251500000)" change="2% so với tháng trước" trend="down" icon="credit-card" tone="expense" />
                <x-ui.stat-card label="Thu nhập" :value="Format::money(28500000)" :compact-value="Format::compactMoney(28500000)" change="12%" trend="up" icon="banknotes" tone="income" />
                <x-ui.stat-card label="Chi tiêu" :value="Format::money(18200000)" :compact-value="Format::compactMoney(18200000)" icon="shopping-bag" tone="neutral" />
            </div>
            <x-ui.card title="Ngân sách và mục tiêu">
                <div class="grid gap-5 md:grid-cols-2">
                    <x-ui.progress label="Ăn uống" :value="5824000" :max="7000000" detail="5.824.000 đ / 7.000.000 đ" />
                    <x-ui.progress label="Nhà ở" :value="3500000" :max="4000000" detail="3.500.000 đ / 4.000.000 đ" />
                    <x-ui.progress label="Giải trí" :value="1150000" :max="1000000" detail="Vượt 150.000 đ" />
                    <x-ui.progress label="Mua nhà" :value="105000000" :max="300000000" mode="goal" detail="Đã tiết kiệm 105.000.000 đ" />
                </div>
            </x-ui.card>
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Trạng thái tải, trống, lỗi</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <x-ui.card title="Đang tải"><x-ui.skeleton :lines="5" /></x-ui.card>
                <x-ui.card :padding="false">
                    <x-ui.empty-state title="Chưa có giao dịch" description="Thêm giao dịch đầu tiên để bắt đầu theo dõi dòng tiền.">
                        <x-ui.button icon="plus" size="sm">Thêm giao dịch</x-ui.button>
                    </x-ui.empty-state>
                </x-ui.card>
                <x-ui.card :padding="false">
                    <x-ui.error-state><x-ui.button variant="outline" size="sm" icon="arrow-path">Thử lại</x-ui.button></x-ui.error-state>
                </x-ui.card>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <x-ui.alert>Lãi suất mang tính tham khảo, cập nhật theo công bố của ngân hàng.</x-ui.alert>
                <x-ui.alert variant="success" title="Đã lưu">Giao dịch đã được thêm vào ví.</x-ui.alert>
                <x-ui.alert variant="warning" title="Sắp vượt ngân sách">Ăn uống đã dùng 83% ngân sách tháng.</x-ui.alert>
                <x-ui.alert variant="danger" title="Không lưu được">Số tiền phải lớn hơn 0.</x-ui.alert>
            </div>
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Hộp thoại xác nhận và thông báo</h2>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="danger" icon="trash" x-data x-on:click="$dispatch('open-modal', 'xoa-giao-dich')">Xóa giao dịch</x-ui.button>
                <x-ui.button variant="outline" x-data x-on:click="$dispatch('toast', { message: 'Đã lưu giao dịch.', type: 'success' })">Hiện toast thành công</x-ui.button>
                <x-ui.button variant="outline" x-data x-on:click="$dispatch('toast', { message: 'Ăn uống đã dùng 83% ngân sách.', type: 'warning' })">Hiện toast cảnh báo</x-ui.button>
            </div>
            <x-ui.modal name="xoa-giao-dich" title="Xóa giao dịch này?">
                Giao dịch “Siêu thị — 450.000 đ” sẽ bị xóa và số dư ví được tính lại. Thao tác không thể hoàn tác.
                <x-slot:footer>
                    <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Hủy</x-ui.button>
                    <x-ui.button variant="danger" x-on:click="$dispatch('close-modal'); $dispatch('toast', { message: 'Đã xóa giao dịch.', type: 'success' })">Xóa</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Điều hướng</h2>
            <x-ui.breadcrumb :items="['Trang chủ' => '#', 'Sản phẩm tài chính' => '#', 'Thẻ tín dụng' => null]" />
            <x-ui.tabs class="max-w-md" :items="[['label' => 'Thu nhập', 'href' => '#'], ['label' => 'Chi tiêu', 'href' => '#', 'active' => true], ['label' => 'Chuyển tiền', 'href' => '#']]" />
            {{ $paginator->links() }}
        </section>

        <section class="{{ $section }}">
            <h2 class="{{ $h2 }}">Bảng</h2>
            <x-ui.table>
                <thead>
                    <tr>
                        <x-ui.sort-header column="ngan_hang" label="Ngân hàng" />
                        <x-ui.sort-header column="1" label="1 tháng" align="right" />
                        <x-ui.sort-header column="6" label="6 tháng" align="right" />
                        <x-ui.sort-header column="12" label="12 tháng" align="right" />
                        <th class="text-right">24 tháng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([['Ngân hàng mẫu 1', 3.1, 4.6, 5.2, 5.5], ['Ngân hàng mẫu 2', 2.9, 4.4, 5.0, null], ['Ngân hàng mẫu 3', 3.3, 4.8, 5.4, 5.6]] as $row)
                        <tr>
                            <td class="whitespace-nowrap font-medium text-navy-900">{{ $row[0] }}</td>
                            @foreach (array_slice($row, 1) as $rate)
                                <td class="num whitespace-nowrap text-right">{{ Format::percent($rate) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </section>
    </div>
</x-layouts.public>
