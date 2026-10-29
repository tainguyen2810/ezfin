<?php

namespace App\Modules\Planner\Tools;

/**
 * Một công cụ tính toán tại /cong-cu/<slug>. Đầu vào đọc từ query string để chạy được khi tắt JS
 * và để URL giữ nguyên kết quả khi chia sẻ. View: planner::tools.<slug>.form và .result.
 */
abstract class Tool
{
    abstract public function slug(): string;

    abstract public function title(): string;

    /** Một câu mô tả cho danh sách công cụ và thẻ meta. */
    abstract public function summary(): string;

    abstract public function icon(): string;

    /**
     * Giá trị mặc định của từng ô, dạng chuỗi như trên URL.
     *
     * @return array<string, string>
     */
    abstract public function defaults(): array;

    /** @return array<string, mixed> */
    abstract public function rules(): array;

    /** @return array<string, string> */
    public function messages(): array
    {
        return [];
    }

    /**
     * Cách tính và giả định, hiển thị dưới kết quả.
     *
     * @return list<string>
     */
    abstract public function assumptions(): array;

    /**
     * Dữ liệu cho view kết quả, từ đầu vào đã kiểm tra.
     *
     * @param  array<string, string>  $input
     * @return array<string, mixed>
     */
    abstract public function calculate(array $input): array;

    /** @return list<string> ô số tiền: bỏ dấu chấm phân cách nghìn */
    protected function moneyFields(): array
    {
        return [];
    }

    /** @return list<string> ô lãi suất: nhận dấu phẩy thập phân */
    protected function rateFields(): array
    {
        return [];
    }

    /**
     * Đưa query string về dạng gọn: chỉ giữ ô của công cụ có giá trị chuỗi khác mặc định,
     * số tiền bỏ dấu chấm, lãi suất đổi dấu phẩy thành dấu chấm. Giá trị sai vẫn giữ để báo lỗi.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, string>
     */
    public function normalize(array $query): array
    {
        $normalized = [];

        foreach ($this->defaults() as $key => $default) {
            $value = $query[$key] ?? null;
            if (! is_string($value) || ($value = trim($value)) === '') {
                continue;
            }

            if (in_array($key, $this->moneyFields(), true) && preg_match('/^[\d.\s]+$/', $value)) {
                $value = ltrim(preg_replace('/\D/', '', $value), '0') ?: '0';
            }

            if (in_array($key, $this->rateFields(), true)) {
                $value = str_replace(',', '.', $value);
            }

            if ($value !== $default) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
