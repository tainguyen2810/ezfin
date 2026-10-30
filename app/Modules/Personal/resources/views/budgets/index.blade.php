@php
    $over = $rows->where('over', true);
    $warning = $rows->where('warning', true);
    $remaining = $limit - $spent;
@endphp
<x-personal::shell title="Ngân sách">
    <x-slot:actions>
        <div class="hidden sm:block"><x-ui.button :href="route('personal.budgets.edit', ['thang' => $month->format('Y-m')])" icon="pencil-square" size="sm">Thiết lập ngân sách</x-ui.button></div>
    </x-slot:actions>

    <div class="space-y-4">
        @include('personal::budgets._month-nav', ['route' => 'personal.budgets.index', 'label' => 'Ngân sách tháng '.$month->format('m/Y')])

        @if ($rows->isEmpty())
            <x-ui.card>
                <x-ui.empty-state icon="chart-pie" title="Chưa đặt ngân sách cho tháng này"
                    description="Đặt hạn mức chi cho từng danh mục (ăn uống, đi lại...). EzFin sẽ theo dõi số đã chi và nhắc khi sắp vượt.">
                    <div class="flex flex-wrap justify-center gap-2">
                        <x-ui.button :href="route('personal.budgets.edit', ['thang' => $month->format('Y-m')])" icon="plus">Đặt ngân sách</x-ui.button>
                        @if ($hasPrevious)
                            <form method="POST" action="{{ route('personal.budgets.copy') }}">
                                @csrf
                                <input type="hidden" name="thang" value="{{ $month->format('Y-m') }}">
                                <x-ui.button type="submit" variant="outline" icon="arrow-path">Dùng lại tháng trước</x-ui.button>
                            </form>
                        @endif
                    </div>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            @if ($over->isNotEmpty())
                <x-ui.alert variant="danger" title="Đã vượt ngân sách">{{ $over->map(fn ($r) => $r->budget->category->name.' (vượt '.Format::money(-$r->remaining).')')->join(', ') }}.</x-ui.alert>
            @endif
            @if ($warning->isNotEmpty())
                <x-ui.alert variant="warning" title="Sắp hết ngân sách">{{ $warning->map(fn ($r) => $r->budget->category->name.' (đã dùng '.$r->percent.'%)')->join(', ') }}.</x-ui.alert>
            @endif

            <x-ui.card>
                <div class="grid grid-cols-3 gap-3">
                    <div><p class="text-xs text-slate-500 sm:text-sm">Tổng ngân sách</p><p class="num mt-1 font-bold text-navy-900 sm:text-xl"><span class="sm:hidden">{{ Format::compactMoney($limit) }}</span><span class="hidden sm:inline">{{ Format::money($limit) }}</span></p></div>
                    <div><p class="text-xs text-slate-500 sm:text-sm">Đã chi</p><p class="num mt-1 font-bold text-expense sm:text-xl"><span class="sm:hidden">{{ Format::compactMoney($spent) }}</span><span class="hidden sm:inline">{{ Format::money($spent) }}</span></p></div>
                    <div><p class="text-xs text-slate-500 sm:text-sm">{{ $remaining >= 0 ? 'Còn lại' : 'Vượt' }}</p><p @class(['num mt-1 font-bold sm:text-xl', 'text-income' => $remaining >= 0, 'text-expense' => $remaining < 0])><span class="sm:hidden">{{ Format::compactMoney(abs($remaining)) }}</span><span class="hidden sm:inline">{{ Format::money(abs($remaining)) }}</span></p></div>
                </div>
                <x-ui.progress class="mt-4" :value="$spent" :max="$limit" />
            </x-ui.card>

            <x-ui.card title="Theo danh mục" :padding="false">
                <table class="hidden w-full text-sm md:table">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500">
                        <tr><th class="px-5 py-2 font-medium">Danh mục</th><th class="px-5 py-2 text-right font-medium">Ngân sách</th><th class="px-5 py-2 text-right font-medium">Đã chi</th><th class="px-5 py-2 text-right font-medium">Còn lại</th><th class="w-48 px-5 py-2 font-medium">Tỷ lệ</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-5 py-3"><span class="flex items-center gap-2"><x-personal::category-icon :category="$row->budget->category" size="sm" /><span class="font-medium text-navy-900">{{ $row->budget->category->name }}</span></span></td>
                                <td class="num px-5 py-3 text-right">{{ Format::money($row->limit) }}</td>
                                <td class="num px-5 py-3 text-right text-expense">{{ Format::money($row->spent) }}</td>
                                <td @class(['num px-5 py-3 text-right', 'text-expense font-semibold' => $row->over])>{{ $row->over ? '−' : '' }}{{ Format::money(abs($row->remaining)) }}</td>
                                <td class="px-5 py-3"><x-ui.progress :value="$row->spent" :max="$row->limit" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <ul class="divide-y divide-slate-100 md:hidden">
                    @foreach ($rows as $row)
                        <li class="flex gap-3 px-4 py-3">
                            <x-personal::category-icon :category="$row->budget->category" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate font-medium text-navy-900">{{ $row->budget->category->name }}</p>
                                    <p class="num shrink-0 text-xs text-slate-500">{{ Format::money($row->spent) }} / {{ Format::money($row->limit) }}</p>
                                </div>
                                <x-ui.progress class="mt-2" :value="$row->spent" :max="$row->limit" :detail="$row->over ? 'Vượt '.Format::money(-$row->remaining) : 'Còn '.Format::money($row->remaining)" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>

            <x-ui.button :href="route('personal.budgets.edit', ['thang' => $month->format('Y-m')])" icon="pencil-square" variant="outline" class="w-full sm:hidden">Thiết lập ngân sách</x-ui.button>
            <p class="text-xs text-slate-500">Đã chi tính theo giao dịch chi tiêu trong tháng của các danh mục có ngân sách; chuyển tiền giữa các ví không tính.</p>
        @endif
    </div>
</x-personal::shell>
