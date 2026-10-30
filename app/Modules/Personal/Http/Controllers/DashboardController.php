<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Models\Wallet;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(): View
    {
        $wallets = Wallet::active()->withBalance()->orderBy('name')->get();

        return view('personal::dashboard', [
            'wallets' => $wallets,
            'total' => $wallets->where('include_in_total', true)->sum(fn (Wallet $wallet) => $wallet->balance()),
        ]);
    }
}
