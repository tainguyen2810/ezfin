<?php

namespace App\Modules\Planner\Tools;

use App\Modules\Planner\Calculators\DepositInterest;
use App\Modules\Planner\Enums\DepositPayout;
use Illuminate\Validation\Rule;

class DepositInterestTool extends Tool
{
    public function slug(): string
    {
        return 'lai-tien-gui';
    }

    public function title(): string
    {
        return 'Tính lãi tiền gửi tiết kiệm';
    }

    public function summary(): string
    {
        return 'Tiền lãi nhận được khi gửi tiết kiệm có kỳ hạn, lĩnh lãi cuối kỳ hoặc hằng tháng.';
    }

    public function icon(): string
    {
        return 'banknotes';
    }

    public function defaults(): array
    {
        return [
            'so_tien' => '100000000',
            'lai_suat' => '5.5',
            'ky_han' => '12',
            'linh_lai' => DepositPayout::EndOfTerm->value,
        ];
    }

    protected function moneyFields(): array
    {
        return ['so_tien'];
    }

    protected function rateFields(): array
    {
        return ['lai_suat'];
    }

    public function rules(): array
    {
        return [
            'so_tien' => ['required', 'integer', 'min:1000000', 'max:100000000000'],
            'lai_suat' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'numeric', 'max:20'],
            'ky_han' => ['required', 'integer', 'min:1', 'max:60'],
            'linh_lai' => ['required', Rule::enum(DepositPayout::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'so_tien.*' => 'Nhập số tiền gửi từ 1.000.000 đ đến 100 tỷ đồng.',
            'lai_suat.*' => 'Nhập lãi suất từ 0 đến 20%/năm, tối đa 2 chữ số thập phân.',
            'ky_han.*' => 'Nhập kỳ hạn từ 1 đến 60 tháng.',
            'linh_lai.*' => 'Chọn cách lĩnh lãi.',
        ];
    }

    public function assumptions(): array
    {
        return [
            'Tiền lãi = số tiền gửi × lãi suất (%/năm) × số tháng / 12, làm tròn đến đồng. Ngân hàng thường tính theo số ngày thực gửi / 365 nên số thực nhận có thể lệch một ít.',
            'Lĩnh lãi hằng tháng: mỗi tháng nhận số tiền gửi × lãi suất / 12. Ngân hàng thường áp lãi suất thấp hơn cho cách lĩnh này; hãy nhập đúng mức ngân hàng công bố.',
            'Gửi đủ kỳ hạn; rút trước hạn thường chỉ hưởng lãi không kỳ hạn.',
            'Không tính tái tục và chưa trừ thuế, phí.',
        ];
    }

    public function calculate(array $input): array
    {
        return [
            'result' => DepositInterest::calculate((int) $input['so_tien'], $input['lai_suat'], (int) $input['ky_han'], DepositPayout::from($input['linh_lai'])),
            'months' => (int) $input['ky_han'],
            'rate' => $input['lai_suat'],
        ];
    }
}
