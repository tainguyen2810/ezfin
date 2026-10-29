<?php

namespace App\Modules\Planner\Tools;

use App\Modules\Planner\Calculators\CompoundInterest;
use App\Modules\Planner\Enums\Compounding;
use Illuminate\Validation\Rule;

class CompoundInterestTool extends Tool
{
    public function slug(): string
    {
        return 'lai-kep';
    }

    public function title(): string
    {
        return 'Tính lãi kép';
    }

    public function summary(): string
    {
        return 'Số tiền tích lũy được sau nhiều năm khi tiền lãi được cộng vào gốc, có góp thêm hằng tháng.';
    }

    public function icon(): string
    {
        return 'arrow-trending-up';
    }

    public function defaults(): array
    {
        return [
            'so_tien' => '100000000',
            'gop_them' => '2000000',
            'lai_suat' => '6',
            'so_nam' => '10',
            'ghep_lai' => Compounding::Monthly->value,
        ];
    }

    protected function moneyFields(): array
    {
        return ['so_tien', 'gop_them'];
    }

    protected function rateFields(): array
    {
        return ['lai_suat'];
    }

    public function rules(): array
    {
        return [
            'so_tien' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'gop_them' => ['required', 'integer', 'min:0', 'max:10000000000'],
            'lai_suat' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'numeric', 'max:30'],
            'so_nam' => ['required', 'integer', 'min:1', 'max:50'],
            'ghep_lai' => ['required', Rule::enum(Compounding::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'so_tien.*' => 'Nhập số tiền ban đầu từ 0 đến 100 tỷ đồng.',
            'gop_them.*' => 'Nhập số tiền góp thêm mỗi tháng từ 0 đến 10 tỷ đồng.',
            'lai_suat.*' => 'Nhập lãi suất từ 0 đến 30%/năm, tối đa 2 chữ số thập phân.',
            'so_nam.*' => 'Nhập số năm từ 1 đến 50.',
            'ghep_lai.*' => 'Chọn kỳ ghép lãi.',
        ];
    }

    public function assumptions(): array
    {
        return [
            'Lãi suất cố định trong suốt thời gian tính.',
            'Lãi phát sinh theo tháng trên số dư đầu tháng (lãi suất năm / 12) và được cộng vào gốc vào cuối mỗi kỳ ghép lãi; tiền lãi làm tròn đến đồng.',
            'Khoản góp thêm nộp vào cuối mỗi tháng nên bắt đầu sinh lãi từ tháng sau.',
            'Chưa trừ thuế, phí và lạm phát.',
        ];
    }

    public function calculate(array $input): array
    {
        $compounding = Compounding::from($input['ghep_lai']);

        return [
            'result' => CompoundInterest::calculate((int) $input['so_tien'], (int) $input['gop_them'], $input['lai_suat'], (int) $input['so_nam'], $compounding),
            'years' => (int) $input['so_nam'],
        ];
    }
}
