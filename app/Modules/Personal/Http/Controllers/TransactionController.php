<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\DeleteTransaction;
use App\Modules\Personal\Actions\RecordTransaction;
use App\Modules\Personal\Actions\RecordTransfer;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Http\Requests\TransactionRequest;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use App\Modules\Personal\Support\Month;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController
{
    /** Kiểu trên URL (?loai=) → kiểu form. */
    private const FORM_TYPES = ['thu' => 'income', 'chi' => 'expense', 'chuyen' => 'transfer'];

    public function index(Request $request): View
    {
        $month = Month::fromQuery($request->query('thang'));
        $walletId = $request->integer('vi') ?: null;
        $categoryId = $request->integer('danh_muc') ?: null;
        $kind = self::FORM_TYPES[$request->query('loai')] ?? null;

        $filtered = Transaction::query()->inMonth($month)
            ->when($walletId, fn ($q) => $q->where('wallet_id', $walletId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

        $totals = (clone $filtered)->incomeOrExpense()
            ->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');

        $transactions = (clone $filtered)
            ->when($kind === 'transfer', fn ($q) => $q->whereIn('type', [TransactionType::TransferIn, TransactionType::TransferOut]))
            ->when($kind && $kind !== 'transfer', fn ($q) => $q->where('type', $kind))
            ->with(['wallet', 'category'])
            ->orderByDesc('occurred_on')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('personal::transactions.index', [
            'transactions' => $transactions,
            'month' => $month,
            'income' => (int) round((float) ($totals[TransactionType::Income->value] ?? 0)),
            'expense' => (int) round((float) ($totals[TransactionType::Expense->value] ?? 0)),
            'wallets' => Wallet::orderBy('name')->get(),
            'categories' => Category::active()->orderBy('type')->orderBy('sort')->get(),
            'filters' => ['vi' => $walletId, 'danh_muc' => $categoryId, 'loai' => $request->query('loai')],
        ]);
    }

    public function create(Request $request, CreateDefaultCategories $defaults): View|RedirectResponse
    {
        if (! Wallet::active()->exists()) {
            return redirect()->route('personal.wallets.create')
                ->with('toast', ['message' => 'Hãy thêm ví trước khi ghi giao dịch.', 'type' => 'warning']);
        }

        $defaults->handle($request->user());
        $type = self::FORM_TYPES[$request->query('loai')] ?? 'expense';

        return $this->form(new Transaction(['occurred_on' => today()]), $type);
    }

    public function store(TransactionRequest $request, RecordTransaction $record, RecordTransfer $transfer): RedirectResponse
    {
        $data = $request->validated();
        $data['type'] === 'transfer' ? $transfer->handle($data) : $record->handle($data);

        $month = Carbon::parse($data['occurred_on'])->format('Y-m');

        return redirect()->route('personal.transactions.index', ['thang' => $month])
            ->with('toast', ['message' => 'Đã lưu giao dịch.', 'type' => 'success']);
    }

    public function edit(Request $request, Transaction $transaction): View
    {
        Gate::authorize('update', $transaction);
        $type = self::FORM_TYPES[$request->query('loai')] ?? ($transaction->type->isTransfer() ? 'transfer' : $transaction->type->value);

        return $this->form($transaction, $type);
    }

    public function update(TransactionRequest $request, Transaction $transaction, RecordTransaction $record, RecordTransfer $transfer): RedirectResponse
    {
        Gate::authorize('update', $transaction);
        $data = $request->validated();

        DB::transaction(function () use ($data, $transaction, $record, $transfer) {
            $groupId = $transaction->transfer_group_id;

            if ($data['type'] === 'transfer') {
                // Thu/chi đổi thành chuyển tiền: bỏ dòng cũ, tạo hai vế mới.
                if (! $groupId) {
                    $transaction->delete();
                }
                $transfer->handle($data, $groupId);

                return;
            }

            // Chuyển tiền đổi thành thu/chi: bỏ vế còn lại, giữ dòng đang sửa.
            if ($groupId) {
                Transaction::where('transfer_group_id', $groupId)->whereKeyNot($transaction->id)->delete();
            }
            $record->handle($data, $transaction);
        });

        return redirect()->route('personal.transactions.index', ['thang' => Carbon::parse($data['occurred_on'])->format('Y-m')])
            ->with('toast', ['message' => 'Đã lưu thay đổi.', 'type' => 'success']);
    }

    public function destroy(Transaction $transaction, DeleteTransaction $delete): RedirectResponse
    {
        Gate::authorize('delete', $transaction);
        $month = $transaction->occurred_on->format('Y-m');
        $delete->handle($transaction);

        return redirect()->route('personal.transactions.index', ['thang' => $month])
            ->with('toast', ['message' => 'Đã xóa giao dịch.', 'type' => 'success']);
    }

    private function form(Transaction $transaction, string $type): View
    {
        $counterpart = $transaction->exists ? $transaction->counterpart() : null;
        [$from, $to] = $transaction->type === TransactionType::TransferIn ? [$counterpart, $transaction] : [$transaction, $counterpart];

        return view('personal::transactions.form', [
            'transaction' => $transaction,
            'type' => $type,
            'types' => array_flip(self::FORM_TYPES),
            // Ví đã ẩn vẫn hiện nếu giao dịch đang sửa thuộc ví đó.
            'wallets' => Wallet::query()
                ->where(fn ($q) => $q->whereNull('archived_at')->orWhereIn('id', array_filter([$from?->wallet_id, $to?->wallet_id])))
                ->orderBy('name')->pluck('name', 'id')->all(),
            'categories' => in_array($type, ['income', 'expense'], true)
                ? Category::active()->ofType(CategoryType::from($type))->orderBy('sort')->orderBy('name')->get()
                : collect(),
            'fromWalletId' => $from?->wallet_id,
            'toWalletId' => $to?->wallet_id,
        ]);
    }
}
