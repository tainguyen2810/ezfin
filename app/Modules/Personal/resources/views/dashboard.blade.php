@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
@endphp
<x-personal::shell title="Tổng quan">
    <div class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><x-ui.icon name="wallet" class="size-6" /></span>
            <div>
                <p class="text-lg font-semibold text-navy-900">{{ $greeting }}, {{ auth()->user()->name }}!</p>
                <p class="text-sm text-slate-500">Cùng xem tình hình tài chính của bạn hôm nay.</p>
            </div>
        </div>

        @if ($wallets->isEmpty())
            <x-ui.card>
                <x-ui.empty-state icon="wallet" title="Bắt đầu bằng ví đầu tiên"
                    description="Thêm ví tiền mặt, tài khoản ngân hàng hoặc ví điện tử với số dư hiện có. Sau đó bạn ghi thu chi để EzFin tính số dư và báo cáo.">
                    <x-ui.button :href="route('personal.wallets.create')" icon="plus">Thêm ví</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                <x-ui.stat-card label="Tiền trong ví" :value="Format::money($total)" :compact-value="Format::compactMoney($total)" icon="wallet" />
            </div>

            <x-ui.card title="Ví & tài khoản">
                <x-slot:action><a href="{{ route('personal.wallets.index') }}" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                <ul class="divide-y divide-slate-100">
                    @foreach ($wallets as $wallet)
                        <li class="flex items-center gap-3 py-3">
                            <span class="flex size-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-ui.icon :name="$wallet->type->icon()" class="size-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-navy-900">{{ $wallet->name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $wallet->type->getLabel() }}{{ $wallet->institution ? ' · '.$wallet->institution : '' }}</span>
                            </span>
                            <span class="num font-semibold text-navy-900">{{ Format::money($wallet->balance()) }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </div>
</x-personal::shell>
