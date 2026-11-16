<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use DOMXPath;
use Illuminate\Support\Carbon;

/**
 * Bắc Á: trang Lãi suất (SharePoint/ASP.NET) mặc định hiện bảng USD; chọn "VNĐ" trong ô DDL_LS là một lần gửi lại form
 * (__doPostBack) kèm các ô ẩn __VIEWSTATE... Bảng VND: Kỳ hạn | lĩnh lãi hàng tháng | hàng quý | cuối kỳ,
 * tiêu đề ghi "Có hiệu lực từ ngày dd/mm/yyyy". Một bảng chung, coi là tại quầy.
 */
class BacA extends SavingsScraper
{
    private const PAYMENTS = [
        'hàng tháng' => InterestPayment::Monthly,
        'hàng quý' => InterestPayment::Quarterly,
        'cuối kỳ' => InterestPayment::EndOfTerm,
    ];

    protected function bank(): string
    {
        return 'bacabank';
    }

    public function url(): string
    {
        return 'https://www.baca-bank.vn/SitePages/website/lai-xuat.aspx?s=LX';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $xpath = new DOMXPath(HtmlTable::document($http->html($this->url())));
        $form = [];
        foreach ($xpath->query('//input[@type="hidden"][@name]') as $input) {
            $form[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $select = $xpath->query('//select[contains(@name, "DDL_LS")]')->item(0);
        $vnd = $select ? $xpath->query('.//option[starts-with(@value, "VND|")]', $select)->item(0) : null;
        if (! $select || ! $vnd || ! isset($form['__VIEWSTATE'])) {
            throw ScrapeException::layoutChanged('không thấy ô chọn loại tiền (DDL_LS) để lấy bảng VND.');
        }
        $form['__EVENTTARGET'] = $select->getAttribute('name');
        $form[$select->getAttribute('name')] = $vnd->getAttribute('value');

        $html = Fetcher::nfc($http->post($this->url(), $form)->body());
        $table = HtmlTable::find($html, 'kỳ hạn', 'lĩnh lãi cuối kỳ');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng VND có cột "Lĩnh lãi cuối kỳ".');
        }

        $grid = HtmlTable::grid($table);
        $columns = [];
        foreach ($grid as $row) {
            foreach ($row as $col => $label) {
                foreach (self::PAYMENTS as $needle => $payment) {
                    if ($col > 0 && str_contains(mb_strtolower($label), 'lĩnh lãi '.$needle)) {
                        $columns[$col] = $payment;
                    }
                }
            }
        }

        foreach ($grid as $row) {
            $term = SavingsParsing::term($row[0] ?? '');
            foreach ($term ? $columns : [] as $col => $payment) {
                $this->row($term, SavingsChannel::Counter, SavingsParsing::rate($row[$col] ?? ''), $payment);
            }
        }

        return preg_match('#hiệu lực từ ngày\s*(\d{1,2})/(\d{1,2})/(\d{4})#u', $html, $m)
            ? Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay()
            : null;
    }
}
