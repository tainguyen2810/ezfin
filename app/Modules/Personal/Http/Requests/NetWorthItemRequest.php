<?php

namespace App\Modules\Personal\Http\Requests;

use App\Modules\Personal\Enums\NetWorthKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NetWorthItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $money = fn (string $key) => preg_replace('/\D/', '', (string) $this->input($key));
        $kind = NetWorthKind::tryFrom((string) $this->input('kind'));

        $this->merge([
            'amount' => $money('amount'),
            'monthly_payment' => $money('monthly_payment') ?: null,
            // Lãi suất gõ kiểu Việt Nam: 9,5 → 9.5. Tài sản không có lãi suất, khoản trả hằng tháng.
            'interest_rate' => $kind?->isDebt() ? (str_replace(',', '.', trim((string) $this->input('interest_rate'))) ?: null) : null,
        ]);
        if ($kind && ! $kind->isDebt()) {
            $this->merge(['monthly_payment' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(NetWorthKind::class)],
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:0', 'max:999999999999999'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'monthly_payment' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'valued_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kind' => 'loại', 'name' => 'tên', 'amount' => 'số tiền', 'interest_rate' => 'lãi suất',
            'monthly_payment' => 'số trả mỗi tháng', 'valued_on' => 'ngày cập nhật', 'note' => 'ghi chú',
        ];
    }
}
