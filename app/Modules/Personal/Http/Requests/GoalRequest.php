<?php

namespace App\Modules\Personal\Http\Requests;

use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'target_amount' => preg_replace('/\D/', '', (string) $this->input('target_amount')),
            'initial_amount' => preg_replace('/\D/', '', (string) $this->input('initial_amount')) ?: '0',
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'integer', 'min:1', 'max:999999999999999'],
            'deadline' => ['nullable', 'date'],
            'icon' => ['required', Rule::in(Goal::ICONS)],
            'color' => ['required', Rule::in(array_keys(Category::COLORS))],
            // Chỉ dùng khi tạo mục tiêu: số tiền đã để dành sẵn trước khi theo dõi.
            'initial_amount' => ['integer', 'min:0', 'max:999999999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên mục tiêu', 'target_amount' => 'số tiền cần', 'deadline' => 'hạn hoàn thành',
            'icon' => 'biểu tượng', 'color' => 'màu', 'initial_amount' => 'số tiền đã có',
        ];
    }
}
