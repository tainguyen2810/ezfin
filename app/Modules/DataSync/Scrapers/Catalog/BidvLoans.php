<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

/**
 * Khoản vay cá nhân BIDV: trang Vay cá nhân liệt kê các trang sản phẩm (/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/<sản phẩm>).
 * BIDV không ghi lãi trên trang sản phẩm ("theo các gói tín dụng ưu đãi từng thời kỳ"), lãi để trống; mức cho vay
 * (% giá trị) và thời hạn tối đa đọc từ chữ trên trang. Trang có khối tin tức kèm lãi của gói khác nên không đọc lãi.
 */
class BidvLoans extends HtmlLoanCatalog
{
    protected function bank(): string
    {
        return 'bidv';
    }

    public function url(): string
    {
        return 'https://bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan';
    }

    protected function productPattern(): string
    {
        return '#^/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/[a-z0-9-]+/?$#';
    }

    protected function readsRate(): bool
    {
        return false;
    }
}
