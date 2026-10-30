<?php

namespace App\Modules\Personal\Http\Requests;

use App\Modules\Personal\Enums\WalletType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Ô tiền gửi "1.000.000" khi tắt JS.
        $this->merge(['opening_balance' => preg_replace('/\D/', '', (string) $this->input('opening_balance')) ?: '0']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(WalletType::class)],
            'institution' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['required', 'integer', 'min:0', 'max:999999999999999'],
            'include_in_total' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên ví', 'type' => 'loại', 'institution' => 'ngân hàng / nhà cung cấp', 'opening_balance' => 'số dư ban đầu'];
    }
}
