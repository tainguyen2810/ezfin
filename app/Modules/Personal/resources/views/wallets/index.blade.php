<x-personal::shell title="Ví & tài khoản">
    <x-slot:actions>
        <div class="hidden sm:block"><x-ui.button :href="route('personal.wallets.create')" icon="plus" size="sm">Thêm ví</x-ui.button></div>
    </x-slot:actions>

    @if ($wallets->isEmpty())
        <x-ui.card>
            <x-ui.empty-state icon="wallet" title="Chưa có ví nào" description="Thêm tiền mặt, tài khoản ngân hàng, ví điện tử hoặc sổ tiết kiệm bạn đang có.">
                <x-ui.button :href="route('personal.wallets.create')" icon="plus">Thêm ví</x-ui.button>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="space-y-4">
            <x-ui.card>
                <p class="text-sm text-slate-500">Tổng tiền trong các ví</p>
                <p class="num mt-1 text-3xl font-bold text-navy-900">{{ Format::money($total) }}</p>
                <p class="mt-1 text-xs text-slate-500">Không gồm ví bạn chọn không tính vào tổng.</p>
            </x-ui.card>

            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($wallets as $wallet)
                    <li class="flex items-center gap-3 rounded-card border border-slate-200 bg-white p-4 shadow-card">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600"><x-ui.icon :name="$wallet->type->icon()" class="size-6" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-navy-900">{{ $wallet->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $wallet->type->getLabel() }}{{ $wallet->institution ? ' · '.$wallet->institution : '' }}{{ $wallet->include_in_total ? '' : ' · Không tính vào tổng' }}</span>
                            <span class="num mt-1 block font-semibold text-navy-900">{{ Format::money($wallet->balance()) }}</span>
                        </span>
                        <a href="{{ route('personal.wallets.edit', $wallet) }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" /><span class="sr-only">Sửa {{ $wallet->name }}</span></a>
                    </li>
                @endforeach
            </ul>

            <x-ui.button :href="route('personal.wallets.create')" icon="plus" variant="outline" class="w-full sm:hidden">Thêm ví</x-ui.button>
        </div>
    @endif
</x-personal::shell>
