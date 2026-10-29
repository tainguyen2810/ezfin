<?php

namespace App\Modules\Planner\Tools;

use App\Modules\Planner\Calculators\Repayment;
use App\Modules\Planner\Enums\RepaymentMethod;
use Illuminate\Validation\Rule;

class LoanTool extends Tool
{
    public function slug(): string
    {
        return 'khoan-vay';
    }

    public function title(): string
    {
        return 'Tính khoản vay';
    }

    public function summary(): string
    {
        return 'Số tiền phải trả mỗi tháng và tổng tiền lãi của khoản vay mua nhà, mua xe, vay tiêu dùng.';
    }

    public function icon(): string
    {
        return 'home';
    }

    public function defaults(): array
    {
        return [
            'so_tien' => '500000000',
            'lai_suat' => '10',
            'so_nam' => '15',
            'cach_tra' => RepaymentMethod::EqualPrincipal->value,
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

    /** @return array<string, string> */
    public static function methods(): array
    {
        return [
            RepaymentMethod::EqualPrincipal->value => RepaymentMethod::EqualPrincipal->label(),
            RepaymentMethod::Annuity->value => RepaymentMethod::Annuity->label(),
        ];
    }

    public function rules(): array
    {
        return [
            'so_tien' => ['required', 'integer', 'min:1000000', 'max:100000000000'],
            'lai_suat' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'numeric', 'max:40'],
            'so_nam' => ['required', 'integer', 'min:1', 'max:35'],
            'cach_tra' => ['required', Rule::in(array_keys(self::methods()))],
        ];
    }

    public function messages(): array
    {
        return [
            'so_tien.*' => 'Nhập số tiền vay từ 1.000.000 đ đến 100 tỷ đồng.',
            'lai_suat.*' => 'Nhập lãi suất từ 0 đến 40%/năm, tối đa 2 chữ số thập phân.',
            'so_nam.*' => 'Nhập thời hạn vay từ 1 đến 35 năm.',
            'cach_tra.*' => 'Chọn cách trả nợ.',
        ];
    }

    public function assumptions(): array
    {
        return [
            'Lãi suất cố định suốt thời hạn vay. Thực tế nhiều khoản vay chỉ cố định 6–24 tháng đầu rồi thả nổi theo lãi suất cơ sở cộng biên độ.',
            'Lãi mỗi tháng = dư nợ còn lại × lãi suất / 12, làm tròn đến đồng. Ngân hàng thường tính theo số ngày thực tế / 365 nên số thực tế lệch một ít.',
            'Dư nợ giảm dần: gốc chia đều mỗi tháng, tiền lãi giảm dần nên tháng đầu trả nhiều nhất. Trả đều: mỗi tháng trả cùng một số tiền, tháng cuối điều chỉnh vài đồng cho hết nợ.',
            'Chưa gồm phí trả nợ trước hạn, phí bảo hiểm khoản vay và phí hồ sơ.',
        ];
    }

    public function calculate(array $input): array
    {
        $method = RepaymentMethod::from($input['cach_tra']);
        $other = $method === RepaymentMethod::EqualPrincipal ? RepaymentMethod::Annuity : RepaymentMethod::EqualPrincipal;
        $months = (int) $input['so_nam'] * 12;

        return [
            'schedule' => Repayment::schedule((int) $input['so_tien'], $input['lai_suat'], $months, $method),
            'alternative' => Repayment::schedule((int) $input['so_tien'], $input['lai_suat'], $months, $other),
        ];
    }
}
