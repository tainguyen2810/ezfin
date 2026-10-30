<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Http\Requests\WalletRequest;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WalletController
{
    public function index(): View
    {
        $wallets = Wallet::active()->orderBy('type')->orderBy('name')->get();

        return view('personal::wallets.index', [
            'wallets' => $wallets,
            'total' => $wallets->where('include_in_total', true)->sum(fn (Wallet $wallet) => $wallet->balance()),
        ]);
    }

    public function create(): View
    {
        return view('personal::wallets.form', ['wallet' => new Wallet(['include_in_total' => true])]);
    }

    public function store(WalletRequest $request): RedirectResponse
    {
        Wallet::create($request->validated());

        return redirect()->route('personal.wallets.index')->with('toast', ['message' => 'Đã thêm ví.', 'type' => 'success']);
    }

    public function edit(Wallet $wallet): View
    {
        Gate::authorize('update', $wallet);

        return view('personal::wallets.form', ['wallet' => $wallet]);
    }

    public function update(WalletRequest $request, Wallet $wallet): RedirectResponse
    {
        Gate::authorize('update', $wallet);
        $wallet->update($request->validated());

        return redirect()->route('personal.wallets.index')->with('toast', ['message' => 'Đã lưu thay đổi.', 'type' => 'success']);
    }

    /** Ví được lưu trữ (ẩn) thay vì xóa hẳn, để giao dịch cũ vẫn còn ví gốc. */
    public function destroy(Wallet $wallet): RedirectResponse
    {
        Gate::authorize('delete', $wallet);
        $wallet->forceFill(['archived_at' => now()])->save();

        return redirect()->route('personal.wallets.index')->with('toast', ['message' => 'Đã ẩn ví "'.$wallet->name.'".', 'type' => 'success']);
    }
}
