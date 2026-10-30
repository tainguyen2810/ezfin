<?php

namespace App\Modules\Personal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Thu, chi (type = income|expense) hoặc chuyển tiền (type = transfer). Ví và danh mục phải của chính người dùng. */
class TransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['amount' => preg_replace('/\D/', '', (string) $this->input('amount'))]);
    }

    public function rules(): array
    {
        $owned = fn (string $table) => Rule::exists($table, 'id')->where('user_id', $this->user()->id);

        $rules = [
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999999'],
            'occurred_on' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->input('type') === 'transfer') {
            return $rules + [
                'from_wallet_id' => ['required', 'integer', $owned('wallets')],
                'to_wallet_id' => ['required', 'integer', 'different:from_wallet_id', $owned('wallets')],
            ];
        }

        return $rules + [
            'wallet_id' => ['required', 'integer', $owned('wallets')],
            'category_id' => ['required', 'integer', $owned('categories')->where('type', $this->input('type'))],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'số tiền', 'occurred_on' => 'ngày', 'note' => 'ghi chú', 'wallet_id' => 'ví',
            'category_id' => 'danh mục', 'from_wallet_id' => 'ví chuyển đi', 'to_wallet_id' => 'ví nhận',
        ];
    }

    public function messages(): array
    {
        return ['to_wallet_id.different' => 'Ví nhận phải khác ví chuyển đi.', 'amount.min' => 'Số tiền phải lớn hơn 0.'];
    }
}
