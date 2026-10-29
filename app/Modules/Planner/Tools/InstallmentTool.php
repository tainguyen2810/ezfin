<?php

namespace App\Modules\Planner\Tools;

use App\Modules\Planner\Calculators\Repayment;
use App\Modules\Planner\Enums\RepaymentMethod;
use Illuminate\Validation\Rule;

class InstallmentTool extends Tool
{
    public function slug(): string
    {
        return 'tra-gop';
    }

    public function title(): string
    {
        return 'Tính trả góp';
    }

    public function summary(): string
    {
        return 'Số tiền trả góp mỗi tháng khi mua điện thoại, xe máy, đồ gia dụng; so sánh lãi phẳng với dư nợ giảm dần.';
    }

    public function icon(): string
    {
        return 'shopping-bag';
    }

    public function defaults(): array
    {
        return [
            'gia' => '20000000',
            'tra_truoc' => '30',
            'lai_suat' => '15',
            'so_thang' => '12',
            'cach_tinh' => RepaymentMethod::Flat->value,
        ];
    }

    protected function moneyFields(): array
    {
        return ['gia'];
    }

    protected function rateFields(): array
    {
        return ['lai_suat'];
    }

    /** @return array<string, string> */
    public static function methods(): array
    {
        return [
            RepaymentMethod::Flat->value => RepaymentMethod::Flat->label(),
            RepaymentMethod::EqualPrincipal->value => RepaymentMethod::EqualPrincipal->label(),
        ];
    }

    public function rules(): array
    {
        return [
            'gia' => ['required', 'integer', 'min:500000', 'max:10000000000'],
            'tra_truoc' => ['required', 'integer', 'min:0', 'max:90'],
            'lai_suat' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'numeric', 'max:60'],
            'so_thang' => ['required', 'integer', 'min:1', 'max:60'],
            'cach_tinh' => ['required', Rule::in(array_keys(self::methods()))],
        ];
    }

    public function messages(): array
    {
        return [
            'gia.*' => 'Nhập giá sản phẩm từ 500.000 đ đến 10 tỷ đồng.',
            'tra_truoc.*' => 'Nhập tỷ lệ trả trước từ 0 đến 90%, số nguyên.',
            'lai_suat.*' => 'Nhập lãi suất từ 0 đến 60%/năm, tối đa 2 chữ số thập phân.',
            'so_thang.*' => 'Nhập kỳ hạn từ 1 đến 60 tháng.',
            'cach_tinh.*' => 'Chọn cách tính lãi.',
        ];
    }

    public function assumptions(): array
    {
        return [
            'Số tiền vay = giá sản phẩm − số tiền trả trước (giá × tỷ lệ trả trước, làm tròn xuống đến đồng).',
            'Lãi phẳng: lãi mỗi tháng tính trên số tiền vay ban đầu dù gốc đã trả bớt, nên chi phí thực tế cao hơn mức lãi suất ghi trên hợp đồng. Lãi suất tương đương là mức lãi tính trên dư nợ giảm dần cho cùng dòng tiền trả.',
            'Dư nợ giảm dần: gốc chia đều mỗi tháng, lãi tính trên dư nợ còn lại.',
            'Chưa gồm phí hồ sơ, phí thu hộ, bảo hiểm khoản vay; nếu có, chi phí thực tế cao hơn.',
            'Trả góp 0% thường đi kèm phí chuyển đổi hoặc giá bán cao hơn mua thẳng; hãy so sánh tổng số tiền phải trả.',
        ];
    }

    public function calculate(array $input): array
    {
        $price = (int) $input['gia'];
        $downPayment = intdiv($price * (int) $input['tra_truoc'], 100);
        $method = RepaymentMethod::from($input['cach_tinh']);
        $other = $method === RepaymentMethod::Flat ? RepaymentMethod::EqualPrincipal : RepaymentMethod::Flat;

        return [
            'price' => $price,
            'downPayment' => $downPayment,
            'rate' => $input['lai_suat'],
            'schedule' => Repayment::schedule($price - $downPayment, $input['lai_suat'], (int) $input['so_thang'], $method),
            'alternative' => Repayment::schedule($price - $downPayment, $input['lai_suat'], (int) $input['so_thang'], $other),
        ];
    }
}
