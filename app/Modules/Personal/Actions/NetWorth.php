<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Models\NetWorthItem;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Support\Collection;

/**
 * Tài sản ròng của người đang đăng nhập = tiền trong ví (tính vào tổng) + tài sản nhập tay − nợ.
 * Dùng cho màn hình Tài sản & nợ và trang Tổng quan.
 */
class NetWorth
{
    /**
     * @return object{wallets: Collection<int, Wallet>, assets: Collection<int, NetWorthItem>, debts: Collection<int, NetWorthItem>,
     *     cash: int, assetTotal: int, debtTotal: int, total: int, debtRatio: ?int, monthlyPayment: int}
     */
    public function handle(): object
    {
        $wallets = Wallet::active()->withBalance()->where('include_in_total', true)->orderBy('type')->orderBy('name')->get();
        [$debts, $assets] = NetWorthItem::orderByDesc('amount')->orderBy('name')->get()->partition->isDebt();

        $cash = $wallets->sum(fn (Wallet $wallet) => $wallet->balance());
        $assetTotal = $cash + $assets->sum(fn (NetWorthItem $item) => $item->amountInt());
        $debtTotal = $debts->sum(fn (NetWorthItem $item) => $item->amountInt());

        return (object) [
            'wallets' => $wallets,
            'assets' => $assets->values(),
            'debts' => $debts->values(),
            'cash' => $cash,
            'assetTotal' => $assetTotal,
            'debtTotal' => $debtTotal,
            'total' => $assetTotal - $debtTotal,
            // Tỷ lệ nợ trên tổng tài sản (%); null khi chưa có tài sản dương.
            'debtRatio' => $assetTotal > 0 ? (int) round($debtTotal / $assetTotal * 100) : null,
            'monthlyPayment' => (int) round($debts->sum(fn (NetWorthItem $item) => (float) $item->monthly_payment)),
        ];
    }
}
