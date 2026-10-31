<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\ProcessRecurringTransactions;
use App\Modules\Personal\Actions\RecordRecurringOccurrence;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Enums\RecurringFrequency;
use App\Modules\Personal\Enums\RecurringMode;
use App\Modules\Personal\Http\Requests\RecurringTransactionRequest;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\RecurringTransaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Khoản thu/chi định kỳ: lương, tiền nhà, trả thẻ, trả góp... tự ghi hoặc nhắc khi đến hạn. */
class RecurringTransactionController
{
    private const FORM_TYPES = ['thu' => 'income', 'chi' => 'expense'];

    public function index(): View
    {
        $items = RecurringTransaction::with(['wallet', 'category'])->orderBy('name')->get();
        $ongoing = $items->whereNotNull('next_due_on')->sortBy(fn (RecurringTransaction $item) => $item->next_due_on->toDateString().'|'.$item->name)->values();
        $monthly = fn (string $type) => (int) round($ongoing->where('type.value', $type)
            ->sum(fn (RecurringTransaction $item) => $item->amountInt() * $item->frequency->perMonth()));

        return view('personal::recurring.index', [
            'ongoing' => $ongoing,
            'ended' => $items->whereNull('next_due_on')->values(),
            'monthlyIncome' => $monthly('income'),
            'monthlyExpense' => $monthly('expense'),
            'today' => today(),
        ]);
    }

    public function create(Request $request, CreateDefaultCategories $defaults): View|RedirectResponse
    {
        if (! Wallet::active()->exists()) {
            return redirect()->route('personal.wallets.create')
                ->with('toast', ['message' => 'Hãy thêm ví trước khi tạo khoản định kỳ.', 'type' => 'warning']);
        }
        $defaults->handle($request->user());
        $type = self::FORM_TYPES[$request->query('loai')] ?? 'expense';

        return $this->form(new RecurringTransaction([
            'type' => $type, 'frequency' => RecurringFrequency::Monthly, 'starts_on' => today(), 'mode' => $type === 'income' ? RecurringMode::Auto : RecurringMode::Confirm,
            'remind_days_before' => $type === 'expense' ? 3 : null,
        ]), $type);
    }

    public function store(RecurringTransactionRequest $request, ProcessRecurringTransactions $process): RedirectResponse
    {
        $item = new RecurringTransaction($request->validated());
        $item->scheduleFrom(today());
        $item->save();

        return $this->saved($item, 'Đã tạo khoản định kỳ.', $process);
    }

    public function edit(Request $request, RecurringTransaction $recurring): View
    {
        Gate::authorize('update', $recurring);
        $type = self::FORM_TYPES[$request->query('loai')] ?? $recurring->type->value;

        return $this->form($recurring, $type);
    }

    public function update(RecurringTransactionRequest $request, RecurringTransaction $recurring, ProcessRecurringTransactions $process): RedirectResponse
    {
        Gate::authorize('update', $recurring);
        $recurring->fill($request->validated());
        $recurring->scheduleFrom(today());
        $recurring->save();

        return $this->saved($recurring, 'Đã lưu thay đổi.', $process);
    }

    public function destroy(RecurringTransaction $recurring): RedirectResponse
    {
        Gate::authorize('delete', $recurring);
        $recurring->delete();

        return redirect()->route('personal.recurring.index')
            ->with('toast', ['message' => 'Đã xóa "'.$recurring->name.'". Các giao dịch đã ghi vẫn được giữ.', 'type' => 'success']);
    }

    /** Đã trả/đã nhận kỳ đang đến hạn: ghi giao dịch ngày hôm nay (khoản chỉ nhắc hạn thì chỉ chuyển sang kỳ sau). */
    public function record(Request $request, RecurringTransaction $recurring, RecordRecurringOccurrence $occurrence): RedirectResponse
    {
        Gate::authorize('update', $recurring);
        $due = $this->expectedDue($request);

        if (! $recurring->mode->recordsTransaction()) {
            return back()->with('toast', $occurrence->skip($recurring, $due)
                ? ['message' => 'Đã đánh dấu "'.$recurring->name.'" là đã thanh toán.', 'type' => 'success']
                : ['message' => 'Kỳ này đã được xử lý trước đó.', 'type' => 'warning']);
        }

        $transaction = $occurrence->record($recurring, $due, today());

        return back()->with('toast', $transaction
            ? ['message' => 'Đã ghi "'.$recurring->name.'" vào giao dịch.', 'type' => 'success']
            : ['message' => 'Kỳ này đã được ghi nhận trước đó.', 'type' => 'warning']);
    }

    public function skip(Request $request, RecurringTransaction $recurring, RecordRecurringOccurrence $occurrence): RedirectResponse
    {
        Gate::authorize('update', $recurring);

        return back()->with('toast', $occurrence->skip($recurring, $this->expectedDue($request))
            ? ['message' => 'Đã bỏ qua kỳ này của "'.$recurring->name.'".', 'type' => 'success']
            : ['message' => 'Kỳ này đã được xử lý trước đó.', 'type' => 'warning']);
    }

    private function expectedDue(Request $request): ?Carbon
    {
        $due = $request->string('due')->toString();

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) ? Carbon::createFromFormat('Y-m-d', $due)->startOfDay() : null;
    }

    /** Khoản tự ghi có kỳ đến hạn hôm nay thì ghi luôn, không chờ lệnh hằng ngày. */
    private function saved(RecurringTransaction $item, string $message, ProcessRecurringTransactions $process): RedirectResponse
    {
        if ($item->mode === RecurringMode::Auto) {
            $process->catchUp($item, today());
        }

        return redirect()->route('personal.recurring.index')->with('toast', ['message' => $message, 'type' => 'success']);
    }

    private function form(RecurringTransaction $item, string $type): View
    {
        $categoryType = CategoryType::from($type);

        return view('personal::recurring.form', [
            'item' => $item,
            'type' => $type,
            'types' => array_flip(self::FORM_TYPES),
            'wallets' => Wallet::query()->where(fn ($q) => $q->active()->orWhere('id', $item->wallet_id))->orderBy('name')->pluck('name', 'id'),
            'categories' => Category::where('type', $categoryType)->where(fn ($q) => $q->active()->orWhere('id', $item->category_id))->orderBy('sort')->get(),
            'frequencies' => RecurringFrequency::options(),
            'modes' => RecurringMode::cases(),
            'remindOptions' => collect(RecurringTransaction::REMIND_OPTIONS)->mapWithKeys(fn (int $days) => [$days => $days === 0 ? 'Đúng ngày đến hạn' : "Trước $days ngày"])->all(),
        ]);
    }
}
