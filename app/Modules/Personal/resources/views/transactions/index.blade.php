@php
    $query = array_filter($filters);
    $prev = route('personal.transactions.index', [...$query, 'thang' => $month->copy()->subMonth()->format('Y-m')]);
    $next = route('personal.transactions.index', [...$query, 'thang' => $month->copy()->addMonth()->format('Y-m')]);
    $tabs = [null => 'Tất cả', 'thu' => 'Thu nhập', 'chi' => 'Chi tiêu', 'chuyen' => 'Chuyển tiền'];
    $byDate = $transactions->getCollection()->groupBy(fn ($t) => $t->occurred_on->toDateString());
@endphp
<x-personal::shell title="Giao dịch">
    <x-slot:actions>
        <x-ui.button :href="route('personal.imports.create', array_filter(['vi' => $filters['vi']]))" icon="arrow-up-tray" size="sm" variant="outline"><span class="hidden sm:inline">Nhập sao kê</span><span class="sr-only sm:hidden">Nhập sao kê</span></x-ui.button>
        <div class="hidden sm:block"><x-ui.button :href="route('personal.transactions.create')" icon="plus" size="sm">Thêm giao dịch</x-ui.button></div>
    </x-slot:actions>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1">
                <a href="{{ $prev }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="chevron-left" /><span class="sr-only">Tháng trước</span></a>
                <h2 class="min-w-32 text-center text-base font-semibold">Tháng {{ $month->format('m/Y') }}</h2>
                <a href="{{ $next }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="chevron-right" /><span class="sr-only">Tháng sau</span></a>
            </div>
            <a href="{{ route('personal.categories.index') }}" class="text-sm font-medium text-primary-700 hover:underline">Quản lý danh mục</a>
        </div>

        @php $net = $income - $expense; @endphp
        <div class="grid grid-cols-3 gap-2 sm:gap-4">
            @foreach ([['Thu nhập', '+', $income, 'text-income'], ['Chi tiêu', '−', $expense, 'text-expense'], ['Chênh lệch', $net >= 0 ? '+' : '−', abs($net), $net >= 0 ? 'text-income' : 'text-expense']] as [$label, $sign, $amount, $color])
                <x-ui.card>
                    <p class="text-xs text-slate-500 sm:text-sm">{{ $label }}</p>
                    <p class="num mt-1 font-bold sm:text-xl {{ $color }}">
                        <span class="sm:hidden">{{ $sign }}{{ Format::compactMoney($amount) }}</span>
                        <span class="hidden sm:inline">{{ $sign }}{{ Format::money($amount) }}</span>
                    </p>
                </x-ui.card>
            @endforeach
        </div>

        <x-ui.card :padding="false">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-3 sm:p-4">
                <div class="-mx-3 flex gap-2 overflow-x-auto px-3 sm:mx-0 sm:px-0">
                @foreach ($tabs as $slug => $label)
                    <a href="{{ route('personal.transactions.index', array_filter([...$query, 'loai' => $slug ?: null, 'thang' => $month->format('Y-m')])) }}"
                        @class(['shrink-0 rounded-full px-3 py-1.5 text-sm font-medium', 'bg-primary-600 text-white' => ($filters['loai'] ?? null) == $slug, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => ($filters['loai'] ?? null) != $slug])>{{ $label }}</a>
                @endforeach
                </div>
                <form method="GET" class="grid w-full grid-cols-2 gap-2 sm:ml-auto sm:flex sm:w-auto">
                    <input type="hidden" name="thang" value="{{ $month->format('Y-m') }}">
                    @if ($filters['loai'])<input type="hidden" name="loai" value="{{ $filters['loai'] }}">@endif
                    <label class="sr-only" for="loc-danh-muc">Danh mục</label>
                    <select id="loc-danh-muc" name="danh_muc" class="h-9 min-w-0 rounded-lg border border-slate-300 bg-white px-2 text-sm" onchange="this.form.submit()">
                        <option value="">Tất cả danh mục</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['danh_muc'] == $category->id)>{{ $category->type->label() }}: {{ $category->name }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="loc-vi">Ví</label>
                    <select id="loc-vi" name="vi" class="h-9 min-w-0 rounded-lg border border-slate-300 bg-white px-2 text-sm" onchange="this.form.submit()">
                        <option value="">Tất cả ví</option>
                        @foreach ($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @selected($filters['vi'] == $wallet->id)>{{ $wallet->name }}</option>
                        @endforeach
                    </select>
                    <noscript><x-ui.button type="submit" size="sm" variant="outline">Lọc</x-ui.button></noscript>
                </form>
            </div>

            @if ($transactions->isEmpty())
                <x-ui.empty-state icon="arrows-right-left" title="Chưa có giao dịch trong tháng này"
                    description="Ghi lại khoản thu, chi hoặc chuyển tiền giữa các ví để theo dõi dòng tiền.">
                    <x-ui.button :href="route('personal.transactions.create')" icon="plus">Thêm giao dịch</x-ui.button>
                </x-ui.empty-state>
            @else
                {{-- Desktop: bảng. --}}
                <table class="hidden w-full text-sm md:table">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500">
                        <tr><th class="px-4 py-2 font-medium">Ngày</th><th class="px-4 py-2 font-medium">Danh mục</th><th class="px-4 py-2 font-medium">Ghi chú</th><th class="px-4 py-2 text-right font-medium">Số tiền</th><th class="px-4 py-2 font-medium">Ví</th><th class="px-4 py-2"><span class="sr-only">Thao tác</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transactions as $t)
                            <tr>
                                <td class="num whitespace-nowrap px-4 py-3 text-slate-600">{{ Format::date($t->occurred_on) }}</td>
                                <td class="px-4 py-3"><span class="flex items-center gap-2"><x-personal::category-icon :category="$t->category" size="sm" /><span class="font-medium text-navy-900">{{ $t->category?->name ?? $t->type->label() }}</span></span></td>
                                <td class="max-w-60 truncate px-4 py-3 text-slate-600">{{ $t->note }}</td>
                                <td @class(['num whitespace-nowrap px-4 py-3 text-right font-semibold', 'text-income' => $t->type->isInflow() && ! $t->type->isTransfer(), 'text-expense' => ! $t->type->isInflow() && ! $t->type->isTransfer(), 'text-navy-900' => $t->type->isTransfer()])>{{ $t->type->isInflow() ? '+' : '−' }}{{ Format::money($t->amountInt()) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $t->wallet->name }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('personal.transactions.edit', $t) }}" class="inline-flex rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" class="size-4" /><span class="sr-only">Sửa</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Mobile: danh sách theo ngày. --}}
                <div class="md:hidden">
                    @foreach ($byDate as $date => $items)
                        <h3 class="num bg-slate-50 px-4 py-1.5 text-xs font-medium text-slate-500">{{ Format::date(\Illuminate\Support\Carbon::parse($date)) }}</h3>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($items as $t)
                                <li>
                                    <a href="{{ route('personal.transactions.edit', $t) }}" class="flex items-center gap-3 px-4 py-3">
                                        <x-personal::category-icon :category="$t->category" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-medium text-navy-900">{{ $t->category?->name ?? $t->type->label() }}</span>
                                            <span class="block truncate text-xs text-slate-500">{{ $t->note ? $t->note.' · ' : '' }}{{ $t->wallet->name }}</span>
                                        </span>
                                        <span @class(['num shrink-0 font-semibold', 'text-income' => $t->type->isInflow() && ! $t->type->isTransfer(), 'text-expense' => ! $t->type->isInflow() && ! $t->type->isTransfer(), 'text-navy-900' => $t->type->isTransfer()])>{{ $t->type->isInflow() ? '+' : '−' }}{{ Format::money($t->amountInt()) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>

                @if ($transactions->hasPages())<div class="border-t border-slate-100 p-4">{{ $transactions->links() }}</div>@endif
            @endif
        </x-ui.card>
    </div>
</x-personal::shell>
