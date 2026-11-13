@if ($change === null)
    <span class="text-slate-400">—</span>
@elseif ($change == 0)
    <span class="text-slate-500">Không đổi</span>
@elseif ($change > 0)
    <span class="text-income">▲ {{ $money ? Format::money($change) : Format::decimal($change) }}</span>
@else
    <span class="text-expense">▼ {{ $money ? Format::money(abs($change)) : Format::decimal(abs($change)) }}</span>
@endif
