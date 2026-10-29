<x-layouts.app title="Tổng quan" :menu="$menu" :bottom="$bottom" add-url="#" user="Nguyễn Văn A">
    <x-slot:toolbar>
        <button type="button" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100">
            <x-ui.icon name="bell" class="size-6" /><span class="sr-only">Thông báo</span>
        </button>
    </x-slot:toolbar>

    <div class="-mx-4 -mt-5 mb-5 sm:-mx-6">@include('core::style-guide._banner')</div>

    <div class="space-y-5">
        <p class="text-slate-600">Chào buổi sáng, Nguyễn Văn A. Đây là tình hình tài chính tháng 10/2026.</p>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
            <x-ui.stat-card label="Tổng tài sản" :value="Format::money(520000000)" :compact-value="Format::compactMoney(520000000)" change="5% so với tháng trước" trend="up" icon="wallet" />
            <x-ui.stat-card label="Tổng nợ" :value="Format::money(251500000)" :compact-value="Format::compactMoney(251500000)" change="2% so với tháng trước" trend="down" icon="credit-card" tone="expense" />
            <x-ui.stat-card label="Thu nhập" :value="Format::money(28500000)" :compact-value="Format::compactMoney(28500000)" icon="banknotes" tone="income" />
            <x-ui.stat-card label="Chi tiêu" :value="Format::money(18200000)" :compact-value="Format::compactMoney(18200000)" icon="shopping-bag" tone="neutral" />
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <x-ui.card title="Ngân sách tháng 10" class="lg:col-span-1">
                <x-slot:action><a href="#" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                <div class="space-y-4">
                    @foreach ($budgets as [$name, $used, $limit])
                        <x-ui.progress :label="$name" :value="$used" :max="$limit" :detail="Format::money($used).' / '.Format::money($limit)" />
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Mục tiêu tài chính">
                <x-slot:action><a href="#" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                <div class="space-y-5">
                    @foreach ($goals as [$name, $saved, $target, $left])
                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="font-semibold text-navy-900">{{ $name }}</p>
                                <p class="num text-sm font-semibold text-navy-900">{{ Format::money($target) }}</p>
                            </div>
                            <x-ui.progress class="mt-2" :value="$saved" :max="$target" mode="goal" :detail="'Đã tiết kiệm '.Format::money($saved).' · '.$left" />
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Giao dịch gần đây" :padding="false">
                <x-slot:action><a href="#" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                <ul class="divide-y divide-slate-100 px-4 pb-2 sm:px-5">
                    @foreach ($transactions as [$name, $wallet, $date, $amount, $icon])
                        <li class="flex items-center gap-3 py-3">
                            <span @class(['flex size-10 shrink-0 items-center justify-center rounded-lg',
                                'bg-income-soft text-income' => $amount > 0,
                                'bg-expense-soft text-expense' => $amount < 0,
                                'bg-slate-100 text-slate-600' => $amount == 0])><x-ui.icon :name="$icon" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-navy-900">{{ $name }}</p>
                                <p class="text-xs text-slate-500">{{ $date }} · {{ $wallet }}</p>
                            </div>
                            <p @class(['num text-sm font-semibold', 'text-income' => $amount > 0, 'text-expense' => $amount < 0, 'text-slate-500' => $amount == 0])>
                                {{ $amount == 0 ? 'Nội bộ' : Format::signedMoney($amount) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>

        <x-ui.card :padding="false">
            <x-ui.empty-state icon="chart-bar" title="Biểu đồ dòng tiền" description="Biểu đồ dùng Chart.js sẽ được thêm cùng công cụ tính toán (task 13–14) và báo cáo (Phase 3)." />
        </x-ui.card>
    </div>
</x-layouts.app>
