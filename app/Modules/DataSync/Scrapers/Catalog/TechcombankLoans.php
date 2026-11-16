<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

/**
 * Khoản vay cá nhân Techcombank: trang Vay liệt kê các trang sản phẩm (/khach-hang-ca-nhan/vay/<nhóm>/<sản phẩm>);
 * trang sản phẩm ghi "Lãi suất vay chỉ từ X%/năm", "Thời gian vay lên đến N năm", "Giá trị cho vay đến N% trên giá trị
 * tài sản", "Hạn mức vay lên đến N tỷ đồng". robots.txt cho phép.
 */
class TechcombankLoans extends HtmlLoanCatalog
{
    protected function bank(): string
    {
        return 'techcombank';
    }

    public function url(): string
    {
        return 'https://techcombank.com/khach-hang-ca-nhan/vay';
    }

    protected function productPattern(): string
    {
        return '#^/khach-hang-ca-nhan/vay/(?!dang-ky|chuong-trinh-uu-dai|cong-cu)[a-z0-9-]+(/[a-z0-9-]+)?/?$#';
    }
}
