@php
    $savingRate = $totals['income'] > 0 ? (int) round($totals['net'] / $totals['income'] * 100) : null;
    $previousLabel = ['thang' => 'tháng trước', 'quy' => 'quý trước', 'nam' => 'năm trước'][$period->kind];
    $diff = fn (int $now, int $before) => $before > 0 ? (int) round(($now - $before) / $before * 100) : null;
    $chartLabels = $monthly->map(fn ($m) => $period->kind === 'nam' ? 'T'.$m['month']->format('n') : $m['month']->format('n/y'))->all();
    $hasData = $totals['income'] + $totals['expense'] > 0;
@endphp
<x-personal::shell title="Báo cáo">
    <div class="space-y-4">
        <x-ui.tabs label="Kỳ báo cáo" class="max-w-md" :items="collect(\App\Modules\Personal\Support\Period::KINDS)->map(fn ($label, $kind) => [
            'label' => $label,
            'href' => route('personal.reports.index', ['ky' => $kind]),
            'active' => $period->kind === $kind,
        ])->values()->all()" />

        <div class="flex items-center gap-1">
            <a href="{{ route('personal.reports.index', $period->previous()->query()) }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="chevron-left" /><span class="sr-only">Kỳ trước</span></a>
            <h2 class="min-w-32 text-center text-base font-semibold">{{ $period->label() }}</h2>
            <a href="{{ route('personal.reports.index', $period->next()->query()) }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="chevron-right" /><span class="sr-only">Kỳ sau</span></a>
        </div>

        <x-ui.card title="Thu - Chi">
            <dl class="grid gap-3 sm:grid-cols-3">
                @foreach ([['Thu nhập', $totals['income'], $previousTotals['income'], 'text-income', 'arrow-down-tray', 'bg-income-soft text-income', true],
                    ['Chi tiêu', $totals['expense'], $previousTotals['expense'], 'text-expense', 'shopping-bag', 'bg-expense-soft text-expense', false],
                    ['Còn lại', $totals['net'], $previousTotals['net'], $totals['net'] < 0 ? 'text-expense' : 'text-primary-700', 'banknotes', 'bg-primary-50 text-primary-600', true]] as [$label, $value, $before, $color, $icon, $iconClasses, $upIsGood])
                    @php
                        $change = $label === 'Còn lại' ? null : $diff($value, $before);
                    @endphp
                    <div class="flex items-center gap-3 sm:block">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $iconClasses }} sm:mb-2"><x-ui.icon :name="$icon" class="size-5" /></span>
                        <div class="flex flex-1 items-baseline justify-between gap-2 sm:block">
                            <dt class="text-sm text-slate-500">{{ $label }}</dt>
                            <dd class="text-right sm:text-left">
                                <span class="num block text-lg font-bold sm:text-xl {{ $color }}">{{ Format::money($value) }}</span>
                                @if ($change !== null)
                                    <span @class(['num block text-xs', 'text-income' => ($change >= 0) === $upIsGood, 'text-expense' => ($change >= 0) !== $upIsGood])>{{ $change >= 0 ? '↑' : '↓' }} {{ abs($change) }}% so với {{ $previousLabel }}</span>
                                @endif
                            </dd>
                        </div>
                    </div>
                @endforeach
            </dl>
            @if ($savingRate !== null)
                <p class="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-600">Tỷ lệ để dành: <strong @class(['num', 'text-expense' => $savingRate < 0, 'text-navy-900' => $savingRate >= 0])>{{ $savingRate }}%</strong> thu nhập.</p>
            @endif
        </x-ui.card>

        @unless ($hasData)
            <x-ui.card>
                <x-ui.empty-state icon="chart-bar" title="Chưa có thu chi trong kỳ này" description="Báo cáo được tính từ giao dịch thu nhập và chi tiêu bạn ghi. Chuyển tiền giữa các ví không tính.">
                    <x-ui.button :href="route('personal.transactions.create')" icon="plus">Thêm giao dịch</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <div class="grid grid-cols-[minmax(0,1fr)] gap-4 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <x-ui.card title="Thu chi theo tháng">
                    <x-ui.bar-chart :framed="false" grouped :caption="'Thu nhập và chi tiêu theo tháng, '.mb_strtolower($period->label()).'.'"
                        :labels="$chartLabels" :label-prefix="$period->kind === 'nam' ? '' : 'Tháng '"
                        :series="[
                            ['label' => 'Thu nhập', 'tone' => 'income', 'values' => $monthly->pluck('income')->all()],
                            ['label' => 'Chi tiêu', 'tone' => 'expense', 'values' => $monthly->pluck('expense')->all()],
                        ]" />
                    <details class="mt-3 text-sm">
                        <summary class="cursor-pointer font-medium text-primary-700">Xem bảng số liệu</summary>
                        <table class="mt-2 w-full">
                            <thead class="text-left text-xs text-slate-500"><tr><th class="py-1 font-medium">Tháng</th><th class="py-1 text-right font-medium">Thu</th><th class="py-1 text-right font-medium">Chi</th><th class="py-1 text-right font-medium">Còn lại</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($monthly as $m)
                                    <tr class="num"><td class="py-1.5">{{ $m['month']->format('m/Y') }}</td><td class="py-1.5 text-right">{{ Format::money($m['income']) }}</td><td class="py-1.5 text-right">{{ Format::money($m['expense']) }}</td><td @class(['py-1.5 text-right font-semibold', 'text-expense' => $m['net'] < 0])>{{ Format::money($m['net']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                </x-ui.card>

                <x-ui.card title="Phân bổ chi tiêu">
                    @if ($spending->isNotEmpty())
                        <x-ui.donut-chart :total="$totals['expense']" center-label="Tổng chi tiêu" :caption="'Chi tiêu '.mb_strtolower($period->label()).' theo danh mục.'"
                            :segments="$spending->map(fn ($row) => ['label' => $row['label'], 'value' => $row['amount'], 'color' => $row['color']])->all()" />
                    @else
                        <p class="py-8 text-center text-sm text-slate-500">Không có khoản chi nào trong kỳ.</p>
                    @endif
                </x-ui.card>
            </div>

            <div class="grid grid-cols-[minmax(0,1fr)] gap-4 lg:grid-cols-2">
                @foreach ([['Chi tiêu theo danh mục', $expenseRows, $totals['expense']], ['Thu nhập theo danh mục', $incomeRows, $totals['income']]] as [$title, $rows, $sum])
                    <x-ui.card :title="$title" :padding="false">
                        @if ($rows->isEmpty())
                            <p class="px-5 py-6 text-center text-sm text-slate-500">Không có số liệu trong kỳ.</p>
                        @else
                            <table class="mt-2 w-full text-sm">
                                <thead class="sr-only"><tr><th>Danh mục</th><th>Số tiền</th><th>Tỷ lệ</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($rows as $row)
                                        <tr>
                                            <td class="py-2.5 pr-2 pl-4 sm:pl-5">
                                                <span class="flex items-center gap-2"><x-personal::category-icon :category="$row['category']" size="sm" /><span class="truncate font-medium text-navy-900">{{ $row['label'] }}</span></span>
                                            </td>
                                            <td class="num py-2.5 pr-2 text-right whitespace-nowrap">{{ Format::money($row['amount']) }}</td>
                                            <td class="num w-14 py-2.5 pr-4 text-right font-semibold text-navy-900 sm:pr-5">{{ $row['percent'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-slate-200 font-semibold"><tr><td class="py-2.5 pl-4 sm:pl-5">Tổng</td><td class="num py-2.5 pr-2 text-right">{{ Format::money($sum) }}</td><td></td></tr></tfoot>
                            </table>
                        @endif
                    </x-ui.card>
                @endforeach
            </div>
            <p class="text-xs text-slate-500">Số liệu tính từ giao dịch thu nhập và chi tiêu bạn đã ghi; chuyển tiền giữa các ví không tính. Đây là công cụ theo dõi, không phải khuyến nghị tài chính.</p>
        @endunless
    </div>
</x-personal::shell>
