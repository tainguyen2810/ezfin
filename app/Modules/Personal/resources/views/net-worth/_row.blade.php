{{-- Một dòng tài sản/khoản nợ nhập tay. --}}
<li class="flex items-center gap-3 px-4 py-3 sm:px-5">
    <span @class(['flex size-10 shrink-0 items-center justify-center rounded-xl', 'bg-expense-soft text-expense' => $item->isDebt(), 'bg-income-soft text-income' => ! $item->isDebt()])><x-ui.icon :name="$item->kind->icon()" class="size-5" /></span>
    <div class="min-w-0 flex-1">
        <p class="truncate font-medium text-navy-900">{{ $item->name }}</p>
        <p @class(['num font-semibold sm:hidden', 'text-expense' => $item->isDebt(), 'text-navy-900' => ! $item->isDebt()])>{{ Format::money($item->amount) }}</p>
        <p class="truncate text-xs text-slate-500">
            {{ $item->kind->label() }}
            @if ($item->interest_rate !== null) · {{ Format::rate($item->interest_rate) }} @endif
            @if ($item->monthly_payment) · trả {{ Format::money($item->monthly_payment) }}/tháng @endif
            · cập nhật {{ Format::date($item->valued_on) }}
        </p>
    </div>
    <p @class(['num hidden shrink-0 font-semibold sm:block', 'text-expense' => $item->isDebt(), 'text-navy-900' => ! $item->isDebt()])>{{ Format::money($item->amount) }}</p>
    <a href="{{ route('personal.net-worth.edit', $item) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" class="size-4" /><span class="sr-only">Sửa {{ $item->name }}</span></a>
</li>
