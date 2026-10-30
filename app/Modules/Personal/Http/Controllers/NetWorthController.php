<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\NetWorth;
use App\Modules\Personal\Http\Requests\NetWorthItemRequest;
use App\Modules\Personal\Models\NetWorthItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NetWorthController
{
    public function index(NetWorth $netWorth): View
    {
        return view('personal::net-worth.index', ['summary' => $netWorth->handle()]);
    }

    /** ?loai=no để thêm khoản nợ, mặc định thêm tài sản. */
    public function create(Request $request): View
    {
        return view('personal::net-worth.form', [
            'item' => new NetWorthItem(['valued_on' => today()]),
            'debt' => $request->query('loai') === 'no',
        ]);
    }

    public function store(NetWorthItemRequest $request): RedirectResponse
    {
        $item = NetWorthItem::create($request->validated());

        return redirect()->route('personal.net-worth.index')
            ->with('toast', ['message' => $item->isDebt() ? 'Đã thêm khoản nợ.' : 'Đã thêm tài sản.', 'type' => 'success']);
    }

    public function edit(NetWorthItem $item): View
    {
        Gate::authorize('update', $item);

        return view('personal::net-worth.form', ['item' => $item, 'debt' => $item->isDebt()]);
    }

    public function update(NetWorthItemRequest $request, NetWorthItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);
        $item->update($request->validated());

        return redirect()->route('personal.net-worth.index')->with('toast', ['message' => 'Đã lưu thay đổi.', 'type' => 'success']);
    }

    public function destroy(NetWorthItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);
        $item->delete();

        return redirect()->route('personal.net-worth.index')->with('toast', ['message' => 'Đã xóa "'.$item->name.'".', 'type' => 'success']);
    }
}
