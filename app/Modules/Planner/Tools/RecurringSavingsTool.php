<?php

namespace App\Modules\Planner\Tools;

use App\Modules\Planner\Calculators\RecurringSavings;

class RecurringSavingsTool extends Tool
{
    public function slug(): string
    {
        return 'tiet-kiem-dinh-ky';
    }

    public function title(): string
    {
        return 'Tính tiết kiệm định kỳ';
    }

    public function summary(): string
    {
        return 'Số tiền tích lũy được khi gửi đều một khoản mỗi tháng vào tài khoản tiết kiệm định kỳ.';
    }

    public function icon(): string
    {
        return 'calendar';
    }

    public function defaults(): array
    {
        return [
            'gui_hang_thang' => '3000000',
            'so_tien_ban_dau' => '0',
            'lai_suat' => '5',
            'so_thang' => '24',
        ];
    }

    protected function moneyFields(): array
    {
        return ['gui_hang_thang', 'so_tien_ban_dau'];
    }

    protected function rateFields(): array
    {
        return ['lai_suat'];
    }

    public function rules(): array
    {
        return [
            'gui_hang_thang' => ['required', 'integer', 'min:100000', 'max:10000000000'],
            'so_tien_ban_dau' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'lai_suat' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'numeric', 'max:20'],
            'so_thang' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'gui_hang_thang.*' => 'Nhập số tiền gửi mỗi tháng từ 100.000 đ đến 10 tỷ đồng.',
            'so_tien_ban_dau.*' => 'Nhập số tiền gửi ban đầu từ 0 đến 100 tỷ đồng.',
            'lai_suat.*' => 'Nhập lãi suất từ 0 đến 20%/năm, tối đa 2 chữ số thập phân.',
            'so_thang.*' => 'Nhập số tháng từ 1 đến 120.',
        ];
    }

    public function assumptions(): array
    {
        return [
            'Gửi vào đầu mỗi tháng, đủ số tháng, lãi suất cố định.',
            'Mỗi khoản gửi hưởng lãi đơn từ tháng gửi đến cuối kỳ: khoản gửi tháng thứ k hưởng (số tháng − k + 1) tháng; số tiền ban đầu hưởng đủ số tháng.',
            'Tiền lãi trả một lần cuối kỳ, làm tròn đến đồng. Ngân hàng thường tính theo số ngày thực gửi / 365 nên số thực nhận có thể lệch một ít.',
            'Chưa tính trường hợp gửi trễ, rút trước hạn, thuế và phí.',
        ];
    }

    public function calculate(array $input): array
    {
        $result = RecurringSavings::calculate((int) $input['gui_hang_thang'], $input['lai_suat'], (int) $input['so_thang'], (int) $input['so_tien_ban_dau']);

        return [
            'result' => $result,
            'months' => (int) $input['so_thang'],
            // Kỳ dài: biểu đồ chỉ lấy cuối mỗi năm và tháng cuối để cột không quá mảnh.
            'chartRows' => (int) $input['so_thang'] > 36
                ? array_values(array_filter($result->months, fn (array $row) => $row['month'] % 12 === 0 || $row['month'] === (int) $input['so_thang']))
                : $result->months,
        ];
    }
}
