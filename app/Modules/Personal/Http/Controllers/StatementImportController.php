<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\ImportStatement;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Imports\StatementImportException;
use App\Modules\Personal\Imports\StatementMapping;
use App\Modules\Personal\Imports\StatementReader;
use App\Modules\Personal\Imports\StatementUploads;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Nhập sao kê ngân hàng (CSV, Excel): tải file → chọn cột, xem trước → nhập vào một ví. */
class StatementImportController
{
    public function __construct(private StatementUploads $uploads) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! Wallet::active()->exists()) {
            return redirect()->route('personal.wallets.create')
                ->with('toast', ['message' => 'Hãy thêm ví hoặc tài khoản ngân hàng trước khi nhập sao kê.', 'type' => 'warning']);
        }

        return view('personal::imports.create', [
            'wallets' => Wallet::active()->orderBy('name')->pluck('name', 'id'),
            'walletId' => $request->integer('vi') ?: null,
        ]);
    }

    public function store(Request $request, StatementReader $reader): RedirectResponse
    {
        $data = $request->validate([
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)],
            'file' => ['required', 'file', 'max:5120', 'extensions:csv,txt,xlsx,xls'],
        ], ['file.extensions' => 'Chỉ nhận file .csv hoặc .xlsx.', 'file.max' => 'File tối đa 5 MB.'], ['wallet_id' => 'ví', 'file' => 'file sao kê']);

        try {
            $rows = $reader->read($request->file('file'));
        } catch (StatementImportException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $token = $this->uploads->put($request->user()->id, [
            'name' => $request->file('file')->getClientOriginalName(),
            'wallet_id' => (int) $data['wallet_id'],
            'rows' => $rows,
        ]);

        return redirect()->route('personal.imports.show', $token);
    }

    public function show(Request $request, string $token, ImportStatement $import, CreateDefaultCategories $defaults): View
    {
        [$upload, $wallet] = $this->upload($request, $token);
        $defaults->handle($request->user());
        $mapping = StatementMapping::fromRequest($request, $upload['rows']);
        $lines = $mapping->problem() ? collect() : $import->preview($upload['rows'], $mapping, $wallet);
        $header = $upload['rows'][$mapping->headerRow] ?? [];
        $width = max(array_map('count', $upload['rows']));

        return view('personal::imports.show', [
            'token' => $token,
            'upload' => $upload,
            'wallet' => $wallet,
            'mapping' => $mapping,
            'lines' => $lines,
            'columns' => collect(range(0, $width - 1))->mapWithKeys(fn (int $i) => [$i => $this->columnLabel($i).(filled($header[$i] ?? null) ? ': '.mb_strimwidth((string) $header[$i], 0, 40, '…') : '')])->all(),
            'sample' => array_slice($upload['rows'], 0, 8),
            'expenseCategories' => Category::active()->where('type', CategoryType::Expense)->orderBy('sort')->pluck('name', 'id'),
            'incomeCategories' => Category::active()->where('type', CategoryType::Income)->orderBy('sort')->pluck('name', 'id'),
            'defaultExpense' => Category::where('type', CategoryType::Expense)->where('name', 'Khác')->value('id'),
            'defaultIncome' => Category::where('type', CategoryType::Income)->where('name', 'Thu khác')->value('id'),
        ]);
    }

    public function import(Request $request, string $token, ImportStatement $import): RedirectResponse
    {
        [$upload, $wallet] = $this->upload($request, $token);
        $owned = fn (CategoryType $type) => Rule::exists('categories', 'id')->where('user_id', $request->user()->id)->where('type', $type->value);
        $data = $request->validate([
            'expense_category_id' => ['required', 'integer', $owned(CategoryType::Expense)],
            'income_category_id' => ['required', 'integer', $owned(CategoryType::Income)],
            'include_possible_duplicates' => ['boolean'],
        ], [], ['expense_category_id' => 'danh mục cho khoản chi', 'income_category_id' => 'danh mục cho khoản thu']);

        $mapping = StatementMapping::fromRequest($request, $upload['rows']);
        abort_if($mapping->problem() !== null, 422);
        $lines = $import->preview($upload['rows'], $mapping, $wallet);
        $result = $import->import($lines, $wallet, Category::findOrFail($data['expense_category_id']),
            Category::findOrFail($data['income_category_id']), (bool) ($data['include_possible_duplicates'] ?? false));
        $this->uploads->forget($request->user()->id, $token);

        $latest = $lines->whereNotNull('date')->max('date');
        $message = "Đã nhập {$result['created']} giao dịch vào {$wallet->name}."
            .($result['duplicates'] ? " Bỏ qua {$result['duplicates']} dòng trùng." : '')
            .($result['errors'] ? " {$result['errors']} dòng lỗi không nhập." : '');

        return redirect()->route('personal.transactions.index', array_filter(['thang' => $latest ? Carbon::parse($latest)->format('Y-m') : null, 'vi' => $wallet->id]))
            ->with('toast', ['message' => $message, 'type' => 'success']);
    }

    /** @return array{0: array{name: string, wallet_id: int, rows: list<list<mixed>>}, 1: Wallet} */
    private function upload(Request $request, string $token): array
    {
        $upload = $this->uploads->get($request->user()->id, $token);
        abort_if($upload === null, 404);

        return [$upload, Wallet::findOrFail($upload['wallet_id'])];
    }

    /** A, B, ..., Z, AA... như tên cột Excel. */
    private function columnLabel(int $index): string
    {
        $label = '';
        for ($i = $index; $i >= 0; $i = intdiv($i, 26) - 1) {
            $label = chr(65 + $i % 26).$label;
        }

        return 'Cột '.$label;
    }
}
