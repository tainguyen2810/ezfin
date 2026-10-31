<x-personal::shell title="Định kỳ & nhắc hạn">
    <x-slot:actions>
        <div class="hidden sm:flex"><x-ui.button :href="route('personal.recurring.create')" icon="plus" size="sm">Thêm khoản định kỳ</x-ui.button></div>
    </x-slot:actions>

    @if ($ongoing->isEmpty() && $ended->isEmpty())
        <x-ui.card>
            <x-ui.empty-state icon="arrow-path" title="Chưa có khoản định kỳ"
                description="Thêm lương, tiền nhà, hóa đơn, trả thẻ tín dụng hay trả góp. EzFin tự ghi giao dịch khi đến hạn, hoặc gửi email nhắc trước để bạn không trễ hạn thanh toán.">
                <x-ui.button :href="route('personal.recurring.create')" icon="plus">Thêm khoản định kỳ</x-ui.button>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <x-ui.stat-card label="Thu cố định mỗi tháng" :value="Format::money($monthlyIncome)" :compact-value="Format::compactMoney($monthlyIncome)" icon="banknotes" tone="income" change="Ước tính từ các khoản đang chạy" />
                <x-ui.stat-card label="Chi cố định mỗi tháng" :value="Format::money($monthlyExpense)" :compact-value="Format::compactMoney($monthlyExpense)" icon="arrow-path" tone="expense" change="Ước tính từ các khoản đang chạy" />
            </div>

            <x-ui.card :padding="false">
                <div class="flex items-center justify-between gap-2 px-4 pt-4 sm:px-5">
                    <h2 class="font-semibold text-navy-900">Sắp đến hạn</h2>
                    <a href="{{ route('personal.recurring.create') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:underline sm:hidden"><x-ui.icon name="plus" class="size-4" />Thêm</a>
                </div>
                <ul class="mt-2 divide-y divide-slate-100">
                    @foreach ($ongoing as $item)
                        <li class="px-4 py-3 sm:px-5">
                            <div class="flex items-center gap-3">
                                @include('personal::recurring._icon', ['size' => 'md'])
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 font-medium text-navy-900">{{ $item->name }}</p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $item->frequency->label() }} @if ($item->wallet) · {{ $item->wallet->name }} @endif
                                        · {{ $item->mode->short() }}@if ($item->remind_days_before !== null) · email {{ $item->remind_days_before === 0 ? 'đúng ngày' : 'trước '.$item->remind_days_before.' ngày' }}@endif
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p @class(['num font-semibold', 'text-income' => $item->type->isInflow(), 'text-navy-900' => ! $item->type->isInflow()])>{{ $item->type->isInflow() ? '+' : '−' }}{{ Format::money($item->amountInt()) }}</p>
                                    <p class="num text-xs text-slate-500">{{ Format::date($item->next_due_on) }}</p>
                                </div>
                                <a href="{{ route('personal.recurring.edit', $item) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" class="size-4" /><span class="sr-only">Sửa {{ $item->name }}</span></a>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2 pl-13">
                                @include('personal::recurring._due')
                                @if ($item->awaitsConfirmation($today))
                                    @include('personal::recurring._actions')
                                @endif
                            </div>
                        </li>
                    @endforeach
                    @if ($ongoing->isEmpty())
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Không còn khoản nào đang chạy.</li>
                    @endif
                </ul>
            </x-ui.card>

            @if ($ended->isNotEmpty())
                <x-ui.card :padding="false">
                    <h2 class="px-4 pt-4 font-semibold text-navy-900 sm:px-5">Đã kết thúc</h2>
                    <ul class="mt-2 divide-y divide-slate-100">
                        @foreach ($ended as $item)
                            <li class="flex items-center gap-3 px-4 py-3 text-slate-500 sm:px-5">
                                @include('personal::recurring._icon', ['size' => 'md'])
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ $item->name }}</p>
                                    <p class="truncate text-xs">{{ $item->frequency->label() }}@if ($item->last_occurrence_on) · kỳ cuối {{ Format::date($item->last_occurrence_on) }}@endif</p>
                                </div>
                                <p class="num shrink-0">{{ Format::money($item->amountInt()) }}</p>
                                <a href="{{ route('personal.recurring.edit', $item) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" class="size-4" /><span class="sr-only">Sửa {{ $item->name }}</span></a>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            <p class="text-xs text-slate-500">"Tự ghi": EzFin ghi giao dịch vào ví lúc 6 giờ sáng ngày đến hạn. "Chờ xác nhận": bấm "Đã trả" sau khi thanh toán để ghi giao dịch. "Chỉ nhắc": nhắc hạn, không ghi giao dịch. Email nhắc gửi tới {{ auth()->user()->email }}.</p>
        </div>
    @endif
</x-personal::shell>
