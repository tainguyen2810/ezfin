<?php

namespace App\Modules\DataSync\Console;

use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\PdfText;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Console\Command;

/**
 * Xem trước một trang nguồn mới (cùng cách tải như bộ lấy dữ liệu: robots.txt, user-agent EzFinBot), để viết bộ đọc
 * cho ngân hàng mới. Không lưu gì.
 *
 *   php artisan ezfin:probe https://.../bieu-lai-suat.pdf     chữ trong PDF, theo trang
 *   php artisan ezfin:probe https://.../lai-suat              các bảng HTML (dòng đầu mỗi bảng)
 */
class ProbeCommand extends Command
{
    protected $signature = 'ezfin:probe {url : Địa chỉ trang hoặc tệp PDF} {--rows=40 : Số dòng tối đa mỗi bảng/trang}';

    protected $description = 'Xem trước nội dung một nguồn dữ liệu (PDF hoặc bảng HTML), không lưu gì';

    public function handle(Fetcher $http): int
    {
        $url = (string) $this->argument('url');
        $limit = max(1, (int) $this->option('rows'));

        try {
            $response = $http->get($url);
            $body = $response->body();

            if (str_starts_with(ltrim(substr($body, 0, 1024)), '%PDF')) {
                foreach (PdfText::pages($body) as $index => $lines) {
                    $this->line('=== Trang '.($index + 1).' ('.count($lines).' dòng)');
                    foreach (array_slice($lines, 0, $limit) as $line) {
                        $this->line(str_replace("\t", ' | ', $line));
                    }
                }

                return self::SUCCESS;
            }

            $tables = HtmlTable::tables(Fetcher::nfc($body));
            $this->line('Trang HTML, '.strlen($body).' byte, '.count($tables).' bảng.');
            foreach ($tables as $index => $table) {
                $this->line('=== Bảng '.($index + 1));
                foreach (array_slice(HtmlTable::grid($table), 0, $limit) as $row) {
                    $this->line(implode(' | ', array_map(fn ($cell) => trim(preg_replace('/\s+/u', ' ', $cell)), $row)));
                }
            }
        } catch (ScrapeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
