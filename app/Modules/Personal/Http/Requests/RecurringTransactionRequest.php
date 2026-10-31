<?php

namespace App\Modules\Personal\Http\Requests;

use App\Modules\Personal\Enums\RecurringFrequency;
use App\Modules\Personal\Enums\RecurringMode;
use App\Modules\Personal\Models\RecurringTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Khoản thu/chi định kỳ. Ví và danh mục phải của chính người dùng. */
class RecurringTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => preg_replace('/\D/', '', (string) $this->input('amount')),
            'remind_days_before' => $this->input('remind_days_before') === '' ? null : $this->input('remind_days_before'),
        ]);
    }

    public function rules(): array
    {
        $owned = fn (string $table) => Rule::exists($table, 'id')->where('user_id', $this->user()->id);

        return [
            'type' => ['required', 'in:income,expense'],
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999999'],
            'mode' => ['required', Rule::enum(RecurringMode::class)],
            // Khoản chỉ nhắc hạn không ghi giao dịch nên ví, danh mục không bắt buộc.
            'wallet_id' => ['required_unless:mode,remind', 'nullable', 'integer', $owned('wallets')],
            'category_id' => ['required_unless:mode,remind', 'nullable', 'integer', $owned('categories')->where('type', $this->input('type'))],
            'frequency' => ['required', Rule::enum(RecurringFrequency::class)],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'remind_days_before' => ['nullable', 'integer', Rule::in(RecurringTransaction::REMIND_OPTIONS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên khoản', 'amount' => 'số tiền', 'wallet_id' => 'ví', 'category_id' => 'danh mục',
            'frequency' => 'chu kỳ', 'starts_on' => 'ngày bắt đầu', 'ends_on' => 'ngày kết thúc', 'remind_days_before' => 'nhắc trước', 'mode' => 'cách ghi',
        ];
    }

    public function messages(): array
    {
        return ['amount.min' => 'Số tiền phải lớn hơn 0.', 'ends_on.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
            'wallet_id.required_unless' => 'Chọn ví để ghi giao dịch.', 'category_id.required_unless' => 'Chọn danh mục để ghi giao dịch.'];
    }
}
