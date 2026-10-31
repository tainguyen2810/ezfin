@php
    use App\Modules\Personal\Actions\ImportStatement;

    $counts = $lines->countBy('status');
    $ready = $counts[ImportStatement::OK] ?? 0;
    $possible = $counts[ImportStatement::POSSIBLE_DUPLICATE] ?? 0;
    $columnOptions = ['' => '— Không dùng —'] + $columns;
    $statuses = [
        ImportStatement::OK => ['success', 'Sẽ nhập'],
        ImportStatement::DUPLICATE => ['neutral', 'Đã nhập trước đó'],
        ImportStatement::POSSIBLE_DUPLICATE => ['warning', 'Có thể trùng'],
        ImportStatement::ERROR => ['danger', 'Lỗi'],
    ];
    $hidden = $mapping->query();
@endphp
<x-personal::shell title="Nhập sao kê ngân hàng">
    <div class="mx-auto grid max-w-4xl grid-cols-[minmax(0,1fr)] gap-4">
        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-navy-900">{{ $upload['name'] }}</p>
                    <p class="text-sm text-slate-500">Nhập vào <strong class="text-navy-900">{{ $wallet->name }}</strong> · {{ count($upload['rows']) }} dòng trong file</p>
                </div>
                <x-ui.button :href="route('personal.imports.create', ['vi' => $wallet->id])" variant="outline" size="sm">Chọn file khác</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="1. Cột trong file">
            <p class="text-sm text-slate-600">EzFin đã đoán sẵn các cột. Kiểm tra với vài dòng đầu của file bên dưới, sửa nếu sai rồi bấm "Xem lại".</p>
            <form method="GET" action="{{ route('personal.imports.show', $token) }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.input name="dong_tieu_de" type="number" min="1" :max="count($upload['rows'])" label="Dòng tiêu đề" :value="$mapping->headerRow + 1" hint="Dòng có tên cột; các dòng dưới nó là giao dịch." />
                <x-ui.select name="date" label="Ngày giao dịch" :options="$columnOptions" :value="$mapping->date" />
                <x-ui.select name="kieu_ngay" label="Kiểu ngày" :options="['dmy' => 'Ngày/tháng/năm', 'mdy' => 'Tháng/ngày/năm']" :value="$mapping->dateFormat" />
                <x-ui.select name="description" label="Nội dung" :options="$columnOptions" :value="$mapping->description" />
                <x-ui.select name="amount" label="Số tiền (một cột, chi là số âm)" :options="$columnOptions" :value="$mapping->amount" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.select name="debit" label="Hoặc: ghi nợ (chi)" :options="$columnOptions" :value="$mapping->debit" />
                    <x-ui.select name="credit" label="Ghi có (thu)" :options="$columnOptions" :value="$mapping->credit" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3"><x-ui.button type="submit" variant="secondary" icon="arrow-path">Xem lại</x-ui.button></div>
            </form>

            <details class="mt-4" @if ($mapping->problem()) open @endif>
                <summary class="cursor-pointer text-sm font-medium text-primary-700">Xem {{ count($sample) }} dòng đầu của file</summary>
                <div class="mt-2 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr><th scope="col" class="px-2 py-1.5 text-left">Dòng</th>@foreach ($columns as $i => $label)<th scope="col" class="px-2 py-1.5 text-left whitespace-nowrap">{{ \Illuminate\Support\Str::before($label, ':') }}</th>@endforeach</tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sample as $r => $row)
                                <tr @class(['bg-highlight/40 font-semibold' => $r === $mapping->headerRow])>
                                    <td class="num px-2 py-1.5 text-slate-500">{{ $r + 1 }}</td>
                                    @foreach ($columns as $i => $label)<td class="max-w-48 truncate px-2 py-1.5 text-navy-900">{{ $row[$i] ?? '' }}</td>@endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </x-ui.card>

        @if ($mapping->problem())
            <x-ui.alert variant="warning">{{ $mapping->problem() }}</x-ui.alert>
        @else
            <x-ui.card title="2. Xem trước" :padding="false">
                <div class="flex flex-wrap gap-2 px-4 pt-2 sm:px-5">
                    @foreach ($statuses as $status => [$variant, $label])
                        @if ($counts[$status] ?? 0)<x-ui.badge :variant="$variant">{{ $label }}: {{ $counts[$status] }}</x-ui.badge>@endif
                    @endforeach
                </div>
                @if ($lines->isEmpty())
                    <p class="px-5 py-6 text-center text-sm text-slate-500">Không đọc được giao dịch nào. Kiểm tra lại dòng tiêu đề và các cột ở trên.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-y border-slate-100 bg-slate-50 text-xs text-slate-500">
                                <tr>
                                    <th scope="col" class="px-4 py-2 text-left sm:pl-5">Ngày</th>
                                    <th scope="col" class="px-2 py-2 text-left">Nội dung</th>
                                    <th scope="col" class="py-2 pr-4 pl-2 text-right sm:pr-2">Số tiền</th>
                                    <th scope="col" class="hidden px-4 py-2 text-left sm:table-cell sm:pr-5">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($lines->take(100) as $line)
                                    <tr>
                                        <td class="num px-4 py-2 whitespace-nowrap text-slate-600 sm:pl-5">{{ $line['date'] ? Format::date($line['date']) : '—' }}</td>
                                        <td class="max-w-72 px-2 py-2 text-navy-900">
                                            <span class="line-clamp-2">{{ $line['description'] ?: '—' }}</span>
                                            @if ($line['status'] !== ImportStatement::OK)<x-ui.badge class="mt-1 sm:hidden" :variant="$statuses[$line['status']][0]">{{ $line['error'] ?? $statuses[$line['status']][1] }}</x-ui.badge>@endif
                                        </td>
                                        <td @class(['num py-2 pr-4 pl-2 text-right font-semibold whitespace-nowrap sm:pr-2', 'text-income' => $line['type']?->isInflow(), 'text-navy-900' => ! $line['type']?->isInflow()])>
                                            {{ $line['amount'] ? ($line['type']->isInflow() ? '+' : '−').Format::money($line['amount']) : '—' }}
                                        </td>
                                        <td class="hidden px-4 py-2 whitespace-nowrap sm:table-cell sm:pr-5">
                                            <x-ui.badge :variant="$statuses[$line['status']][0]">{{ $line['error'] ?? $statuses[$line['status']][1] }}</x-ui.badge>
                                            <span class="sr-only">dòng {{ $line['line'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($lines->count() > 100)<p class="px-5 py-3 text-xs text-slate-500">Hiện 100 trên {{ $lines->count() }} dòng.</p>@endif
                @endif
            </x-ui.card>

            <x-ui.card title="3. Nhập">
                <form method="POST" action="{{ route('personal.imports.import', $token) }}" class="space-y-4">
                    @csrf
                    @foreach ($hidden as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.select name="expense_category_id" label="Danh mục cho khoản chi" :options="$expenseCategories" :value="$defaultExpense" required />
                        <x-ui.select name="income_category_id" label="Danh mục cho khoản thu" :options="$incomeCategories" :value="$defaultIncome" required />
                    </div>
                    <p class="text-xs text-slate-500">Sau khi nhập, bạn có thể đổi danh mục từng giao dịch ở màn hình Giao dịch.</p>
                    @if ($possible)
                        <label class="flex items-start gap-2 text-sm text-navy-900">
                            <input type="hidden" name="include_possible_duplicates" value="0">
                            <input type="checkbox" name="include_possible_duplicates" value="1" class="mt-0.5 size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                            <span>Vẫn nhập {{ $possible }} dòng "có thể trùng" (cùng ngày, cùng số tiền với giao dịch bạn đã ghi tay trong ví này)</span>
                        </label>
                    @endif
                    <x-ui.button type="submit" icon="check" :disabled="$ready + $possible === 0">Nhập {{ $ready }} giao dịch</x-ui.button>
                </form>
            </x-ui.card>
        @endif
    </div>
</x-personal::shell>
