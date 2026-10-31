@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
    $monthLabel = 'tháng '.$month->format('n');
    // "↑ 12% so với tháng trước"; không có số tháng trước thì không so sánh.
    $change = function (int $now, int $before): array {
        if ($before <= 0) {
            return [null, null];
        }
        $percent = (int) round(($now - $before) / $before * 100);

        return [abs($percent).'% so với tháng trước', $percent >= 0 ? 'up' : 'down'];
    };
    [$incomeChange, $incomeTrend] = $change($current['income'], $previous['income']);
    [$expenseChange, $expenseTrend] = $change($current['expense'], $previous['expense']);
    $hasFlow = $monthly->sum(fn ($m) => $m['income'] + $m['expense']) > 0;
    $quick = array_filter([
        ['personal.transactions.index', 'Giao dịch', 'arrows-right-left', 'bg-green-50 text-green-700'],
        ['personal.budgets.index', 'Ngân sách', 'chart-pie', 'bg-blue-50 text-blue-600'],
        ['personal.goals.index', 'Mục tiêu', 'flag', 'bg-violet-50 text-violet-600'],
        ['personal.net-worth.index', 'Tài sản', 'scale', 'bg-amber-50 text-amber-600'],
    ], fn ($item) => Route::has($item[0]));
@endphp
<x-personal::shell title="Tổng quan">
    <div class="space-y-4 lg:space-y-5">
        <div>
            <p class="text-lg font-semibold text-navy-900">{{ $greeting }}, {{ auth()->user()->name }}!</p>
            <p class="text-sm text-slate-500">Tình hình tài chính của bạn {{ $monthLabel }}/{{ $month->format('Y') }}.</p>
        </div>

        @unless ($hasWallets)
            <x-ui.card>
                <x-ui.empty-state icon="wallet" title="Bắt đầu bằng ví đầu tiên"
                    description="Thêm ví tiền mặt, tài khoản ngân hàng hoặc ví điện tử với số dư hiện có. Sau đó bạn ghi thu chi để EzFin tính số dư và báo cáo.">
                    <x-ui.button :href="route('personal.wallets.create')" icon="plus">Thêm ví</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                <x-ui.stat-card label="Tổng tài sản" :value="Format::money($netWorth->assetTotal)" :compact-value="Format::compactMoney($netWorth->assetTotal)" icon="wallet" tone="income" dark />
                <x-ui.stat-card label="Tổng nợ" :value="Format::money($netWorth->debtTotal)" :compact-value="Format::compactMoney($netWorth->debtTotal)" icon="credit-card" tone="expense"
                    :change="$netWorth->debtTotal > 0 ? 'Tài sản ròng '.Format::compactMoney($netWorth->total) : null" />
                <x-ui.stat-card :label="'Thu nhập '.$monthLabel" :value="Format::money($current['income'])" :compact-value="Format::compactMoney($current['income'])" icon="arrow-down-tray" tone="primary"
                    :change="$incomeChange" :trend="$incomeTrend" />
                <x-ui.stat-card :label="'Chi tiêu '.$monthLabel" :value="Format::money($current['expense'])" :compact-value="Format::compactMoney($current['expense'])" icon="shopping-bag" tone="neutral"
                    :change="$expenseChange" :trend="$expenseTrend" :positive="$expenseTrend === null ? null : $expenseTrend === 'down'" />
            </div>

            <nav class="grid grid-cols-4 gap-2 lg:hidden" aria-label="Lối tắt">
                @foreach ($quick as [$route, $label, $icon, $classes])
                    <a href="{{ route($route) }}" class="flex flex-col items-center gap-1.5 rounded-card border border-slate-200 bg-white px-1 py-3 text-xs font-medium text-navy-900 shadow-card">
                        <span class="flex size-10 items-center justify-center rounded-xl {{ $classes }}"><x-ui.icon :name="$icon" class="size-5" /></span>{{ $label }}
                    </a>
                @endforeach
            </nav>

            @if ($upcoming->isNotEmpty())
                <x-ui.card title="Sắp đến hạn" :padding="false">
                    <x-slot:action><a href="{{ route('personal.recurring.index') }}" class="font-medium text-primary-700 hover:underline">Xem tất cả</a></x-slot:action>
                    <ul class="mt-2 divide-y divide-slate-100">
                        @foreach ($upcoming as $item)
                            <li class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 sm:px-5">
                                @include('personal::recurring._icon', ['size' => 'sm'])
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-navy-900">{{ $item->name }}</p>
                                    <p class="num text-xs text-slate-500">{{ Format::date($item->next_due_on) }} · {{ $item->mode->short() }}</p>
                                </div>
                                @include('personal::recurring._due')
                                <p @class(['num w-28 shrink-0 text-right text-sm font-semibold', 'text-income' => $item->type->isInflow(), 'text-navy-900' => ! $item->type->isInflow()])>{{ $item->type->isInflow() ? '+' : '−' }}{{ Format::money($item->amountInt()) }}</p>
                                @if ($item->awaitsConfirmation($today))
                                    <div class="basis-full pl-11">@include('personal::recurring._actions')</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            <div class="grid grid-cols-[minmax(0,1fr)] gap-4 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-5">
                <x-ui.card title="Dòng tiền 6 tháng gần nhất">
                    @if ($hasFlow)
                        <x-ui.bar-chart :framed="false" grouped caption="Thu nhập và chi tiêu 6 tháng gần nhất."
                            :labels="$monthly->map(fn ($m) => 'T'.$m['month']->format('n'))->all()" label-prefix="Tháng "
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
                    @else
                        <x-ui.empty-state icon="chart-bar" title="Chưa có thu chi" description="Ghi thu nhập và chi tiêu để xem dòng tiền theo tháng.">
                            <x-ui.button :href="route('personal.transactions.create')" icon="plus" size="sm">Thêm giao dịch</x-ui.button>
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>

                <x-ui.card :title="'Phân bổ chi tiêu '.$monthLabel">
                    @if (Route::has('personal.reports.index'))
                        <x-slot:action><a href="{{ route('personal.reports.index') }}" class="font-medium text-primary-700">Xem chi tiết</a></x-slot:action>
                    @endif
                    @if ($spending->isNotEmpty())
                        <x-ui.donut-chart :total="$current['expense']" center-label="Tổng chi tiêu" :caption="'Chi tiêu '.$monthLabel.' theo danh mục.'"
                            :segments="$spending->map(fn ($row) => ['label' => $row['label'], 'value' => $row['amount'], 'color' => $row['color']])->all()" />
                    @else
                        <p class="py-8 text-center text-sm text-slate-500">Chưa có khoản chi nào trong {{ $monthLabel }}.</p>
                    @endif
                </x-ui.card>
            </div>

            <div class="grid grid-cols-[minmax(0,1fr)] gap-4 lg:grid-cols-3 lg:gap-5">
                <x-ui.card title="Giao dịch gần đây" :padding="false" class="lg:order-2">
                    <x-slot:action><a href="{{ route('personal.transactions.index') }}" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                    @if ($recent->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-slate-500">Chưa có giao dịch. Bấm nút + để ghi khoản đầu tiên.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recent as $t)
                                <li>
                                    <a href="{{ route('personal.transactions.edit', $t) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 sm:px-5">
                                        <x-personal::category-icon :category="$t->category" size="sm" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-navy-900">{{ $t->note ?: ($t->category?->name ?? $t->type->label()) }}</span>
                                            <span class="num block truncate text-xs text-slate-500">{{ Format::date($t->occurred_on) }} · {{ $t->wallet->name }}</span>
                                        </span>
                                        <span @class(['num shrink-0 text-sm font-semibold', 'text-income' => $t->type->isInflow() && ! $t->type->isTransfer(), 'text-expense' => ! $t->type->isInflow() && ! $t->type->isTransfer(), 'text-navy-900' => $t->type->isTransfer()])>{{ $t->type->isInflow() ? '+' : '−' }}{{ Format::money($t->amountInt()) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>

                <x-ui.card title="Mục tiêu tài chính" class="lg:order-1">
                    <x-slot:action><a href="{{ route('personal.goals.index') }}" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                    @forelse ($goals as $goal)
                        <a href="{{ route('personal.goals.show', $goal) }}" @class(['flex gap-3', 'mt-4 border-t border-slate-100 pt-4' => ! $loop->first])>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $goal->colorClasses() }}"><x-ui.icon :name="$goal->icon" class="size-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-navy-900">{{ $goal->name }}</span>
                                <span class="num block font-semibold text-navy-900">{{ Format::money($goal->target()) }}</span>
                                <x-ui.progress class="mt-1.5" mode="goal" :value="$goal->savedAmount()" :max="$goal->target()"
                                    :detail="'Đã có '.Format::money($goal->savedAmount()).($goal->monthsLeft() ? ' · còn '.$goal->monthsLeft().' tháng' : '')" />
                            </span>
                        </a>
                    @empty
                        <p class="py-4 text-center text-sm text-slate-500">Chưa có mục tiêu đang thực hiện.</p>
                        <x-ui.button :href="route('personal.goals.create')" variant="outline" size="sm" icon="plus" class="w-full">Thêm mục tiêu</x-ui.button>
                    @endforelse
                </x-ui.card>

                <x-ui.card :title="'Ngân sách '.$monthLabel" class="lg:order-3">
                    <x-slot:action><a href="{{ route('personal.budgets.index') }}" class="font-medium text-primary-700">Xem tất cả</a></x-slot:action>
                    @forelse ($budgets as $row)
                        <div @class(['flex gap-3', 'mt-4' => ! $loop->first])>
                            <x-personal::category-icon :category="$row->budget->category" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-navy-900">{{ $row->budget->category->name }}</p>
                                <x-ui.progress class="mt-1" :value="$row->spent" :max="$row->limit" :detail="Format::money($row->spent).' / '.Format::money($row->limit)" />
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-slate-500">Chưa đặt ngân sách cho {{ $monthLabel }}.</p>
                        <x-ui.button :href="route('personal.budgets.edit')" variant="outline" size="sm" icon="plus" class="w-full">Đặt ngân sách</x-ui.button>
                    @endforelse
                </x-ui.card>
            </div>
        @endunless
    </div>
</x-personal::shell>
