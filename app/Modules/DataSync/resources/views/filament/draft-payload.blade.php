@php
    use App\Modules\Compare\Enums\InterestPayment;
    use App\Modules\Compare\Enums\SavingsChannel;
    use App\Support\Format;

    /** @var \App\Modules\DataSync\Models\SyncDraft $draft */
    $draft = $getRecord();
    $kind = $draft->source->kind;
    $key = fn (array $row) => $row['channel'].'|'.$row['payment'].'|'.$row['min_amount'];
    $previous = collect($draft->previousApproved()?->rows() ?? [])->keyBy(fn ($row) => $key($row).'|'.$row['term']);
    $rows = collect($draft->rows());
    $columns = $rows->unique($key)->values();
    $terms = $rows->pluck('term')->unique()->sort()->values();
    $cells = $rows->keyBy(fn ($row) => $key($row).'|'.$row['term']);
@endphp

@if ($kind !== \App\Modules\DataSync\Enums\SyncKind::Savings)
    @php
        $nameKey = $kind === \App\Modules\DataSync\Enums\SyncKind::Fx ? 'currency' : 'product';
        $old = collect($draft->previousApproved()?->rows() ?? [])->keyBy($nameKey);
        $priceColumns = $kind === \App\Modules\DataSync\Enums\SyncKind::Fx
            ? ['buy_cash' => 'Mua tiền mặt', 'buy_transfer' => 'Mua chuyển khoản', 'sell' => 'Bán']
            : ['buy' => 'Mua', 'sell' => 'Bán'];
    @endphp
    <p class="mb-2 text-sm">Công bố lúc {{ \Illuminate\Support\Carbon::parse($draft->payload['quoted_at'])->format('H:i d/m/Y') }}{{ $kind === \App\Modules\DataSync\Enums\SyncKind::Gold ? ', đồng/lượng' : ', đồng cho 1 đơn vị ngoại tệ' }}.</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr>
                    <th class="px-2 py-1 text-start">{{ $kind === \App\Modules\DataSync\Enums\SyncKind::Fx ? 'Ngoại tệ' : 'Loại vàng' }}</th>
                    @foreach ($priceColumns as $label)
                        <th class="px-2 py-1 text-end">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($draft->rows() as $row)
                    <tr class="border-t border-gray-200 dark:border-white/10">
                        <td class="px-2 py-1">{{ $row[$nameKey] }}</td>
                        @foreach (array_keys($priceColumns) as $column)
                            @php($before = $old->get($row[$nameKey])[$column] ?? null)
                            <td class="px-2 py-1 text-end tabular-nums">
                                {{ $row[$column] === null ? '—' : Format::decimal((float) $row[$column]) }}
                                @if ($before !== null && $row[$column] !== null && $before !== $row[$column])
                                    <span class="text-gray-500">({{ Format::decimal((float) $before) }})</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr>
                <th class="px-2 py-1 text-start">Kỳ hạn</th>
                @foreach ($columns as $column)
                    <th class="px-2 py-1 text-end">
                        {{ SavingsChannel::from($column['channel'])->getLabel() }}<br>
                        <span class="font-normal">Lĩnh lãi {{ mb_strtolower(InterestPayment::from($column['payment'])->getLabel()) }}</span>
                        @if ($column['min_amount'] > 0 || $column['max_amount'])
                            <br><span class="font-normal">
                                {{ $column['min_amount'] > 0 ? 'Từ '.Format::money($column['min_amount']) : '' }}
                                {{ $column['max_amount'] ? 'Dưới '.Format::money($column['max_amount']) : '' }}
                            </span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($terms as $term)
                <tr class="border-t border-gray-200 dark:border-white/10">
                    <td class="px-2 py-1">{{ $term === 0 ? 'Không kỳ hạn' : $term.' tháng' }}</td>
                    @foreach ($columns as $column)
                        @php($cellKey = $key($column).'|'.$term)
                        @php($cell = $cells->get($cellKey))
                        @php($old = $previous->get($cellKey))
                        <td class="px-2 py-1 text-end tabular-nums">
                            @if ($cell)
                                {{ Format::percent($cell['rate']) }}
                                @if ($old && $old['rate'] !== $cell['rate'])
                                    <span class="font-semibold">{{ (float) $cell['rate'] > (float) $old['rate'] ? '↑' : '↓' }}</span>
                                    <span class="text-gray-500">({{ Format::percent($old['rate']) }})</span>
                                @elseif ($previous->isNotEmpty() && ! $old)
                                    <span class="text-gray-500">(mới)</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
