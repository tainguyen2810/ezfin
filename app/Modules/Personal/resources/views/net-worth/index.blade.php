@php($s = $summary)
<x-personal::shell title="Tài sản & nợ">
    <x-slot:actions>
        <div class="hidden gap-2 sm:flex">
            <x-ui.button :href="route('personal.net-worth.create', ['loai' => 'no'])" icon="plus" size="sm" variant="outline">Thêm khoản nợ</x-ui.button>
            <x-ui.button :href="route('personal.net-worth.create')" icon="plus" size="sm">Thêm tài sản</x-ui.button>
        </div>
    </x-slot:actions>

    <div class="space-y-4">
        <x-ui.card>
            <p class="text-sm text-slate-500">Tài sản ròng</p>
            <p @class(['num mt-1 text-3xl font-bold', 'text-navy-900' => $s->total >= 0, 'text-expense' => $s->total < 0])>{{ Format::money($s->total) }}</p>
            <p class="mt-1 text-xs text-slate-500">Tổng tài sản trừ tổng nợ.</p>
            <dl class="mt-4 grid gap-2 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3 sm:gap-3">
                <div class="flex items-baseline justify-between gap-2 sm:block"><dt class="text-xs text-slate-500">Tổng tài sản</dt><dd class="num mt-0.5 font-semibold text-income">{{ Format::money($s->assetTotal) }}</dd></div>
                <div class="flex items-baseline justify-between gap-2 sm:block"><dt class="text-xs text-slate-500">Tổng nợ</dt><dd class="num mt-0.5 font-semibold text-expense">{{ Format::money($s->debtTotal) }}</dd></div>
                <div class="flex items-baseline justify-between gap-2 sm:block"><dt class="text-xs text-slate-500">Trả nợ mỗi tháng</dt><dd class="num mt-0.5 font-semibold text-navy-900">{{ $s->monthlyPayment ? Format::money($s->monthlyPayment) : '—' }}</dd></div>
            </dl>
            @if ($s->debtRatio !== null && $s->debtTotal > 0)
                <x-ui.progress class="mt-4" :value="$s->debtTotal" :max="$s->assetTotal" label="Tỷ lệ nợ trên tài sản" />
            @endif
        </x-ui.card>

        <div class="grid grid-cols-[minmax(0,1fr)] items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <x-ui.card :padding="false">
                <div class="flex items-center justify-between gap-2 px-4 pt-4 sm:px-5">
                    <h2 class="font-semibold text-navy-900">Tài sản</h2>
                    <p class="num font-semibold text-income">{{ Format::money($s->assetTotal) }}</p>
                </div>
                <ul class="mt-2 divide-y divide-slate-100">
                    @foreach ($s->wallets as $wallet)
                        <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600"><x-ui.icon :name="$wallet->type->icon()" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-navy-900">{{ $wallet->name }}</p>
                                <p class="num font-semibold text-navy-900 sm:hidden">{{ Format::money($wallet->balance()) }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $wallet->type->getLabel() }} · số dư ví</p>
                            </div>
                            <p class="num hidden shrink-0 font-semibold text-navy-900 sm:block">{{ Format::money($wallet->balance()) }}</p>
                            <a href="{{ route('personal.wallets.edit', $wallet) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" class="size-4" /><span class="sr-only">Sửa ví {{ $wallet->name }}</span></a>
                        </li>
                    @endforeach
                    @foreach ($s->assets as $item)
                        @include('personal::net-worth._row')
                    @endforeach
                    @if ($s->wallets->isEmpty() && $s->assets->isEmpty())
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Chưa có tài sản. Thêm ví ở màn hình Ví hoặc thêm nhà, xe, vàng... ở đây.</li>
                    @endif
                </ul>
                <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
                    <a href="{{ route('personal.net-worth.create') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline"><x-ui.icon name="plus" class="size-4" />Thêm tài sản</a>
                </div>
            </x-ui.card>

            <x-ui.card :padding="false">
                <div class="flex items-center justify-between gap-2 px-4 pt-4 sm:px-5">
                    <h2 class="font-semibold text-navy-900">Nợ</h2>
                    <p class="num font-semibold text-expense">{{ Format::money($s->debtTotal) }}</p>
                </div>
                <ul class="mt-2 divide-y divide-slate-100">
                    @forelse ($s->debts as $item)
                        @include('personal::net-worth._row')
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Chưa có khoản nợ nào. Thêm khoản vay mua nhà, dư nợ thẻ tín dụng... để thấy bức tranh đầy đủ.</li>
                    @endforelse
                </ul>
                <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
                    <a href="{{ route('personal.net-worth.create', ['loai' => 'no']) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline"><x-ui.icon name="plus" class="size-4" />Thêm khoản nợ</a>
                </div>
            </x-ui.card>
        </div>

        <p class="text-xs text-slate-500">Tiền trong ví lấy tự động từ màn hình Ví (chỉ các ví tính vào tổng). Giá trị tài sản và dư nợ do bạn tự nhập, hãy cập nhật định kỳ để số liệu sát thực tế.</p>
    </div>
</x-personal::shell>
