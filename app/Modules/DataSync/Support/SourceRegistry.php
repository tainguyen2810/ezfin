<?php

namespace App\Modules\DataSync\Support;

use App\Modules\DataSync\Contracts\Scraper;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Scrapers\Catalog;
use App\Modules\DataSync\Scrapers\Institutions\BankProfile;
use App\Modules\DataSync\Scrapers\Markets;
use App\Modules\DataSync\Scrapers\Savings;

/**
 * Danh sách bộ lấy dữ liệu. Thêm nguồn mới: tạo class trong Scrapers/<Loại> và thêm vào SCRAPERS.
 * Mỗi ngân hàng trong Banks::ALL tự có một nguồn hồ sơ (tên, website, logo).
 * sync() tạo/cập nhật bản ghi sync_sources tương ứng (giữ nguyên lựa chọn bật/tắt, tự duyệt của quản trị viên).
 */
class SourceRegistry
{
    /** @var list<class-string<Scraper>> */
    public const SCRAPERS = [
        Savings\Vietcombank::class,
        Savings\Bidv::class,
        Savings\Vietinbank::class,
        Savings\Agribank::class,
        Savings\MbBank::class,
        Savings\Acb::class,
        Savings\Tpbank::class,
        Savings\Ocb::class,
        Savings\Techcombank::class,
        Savings\Hdbank::class,
        Savings\Eximbank::class,
        Markets\VietcombankFx::class,
        Markets\SjcGold::class,
        Markets\PnjGold::class,
        Markets\BtmcGold::class,
        Catalog\VietcombankCards::class,
        Catalog\VietcombankLoans::class,
        Catalog\TechcombankCards::class,
        Catalog\BidvCards::class,
        Catalog\VietinbankCards::class,
        Catalog\AcbCards::class,
        Catalog\SacombankCards::class,
        Catalog\AgribankCards::class,
    ];

    /** @var array<string, Scraper>|null */
    private ?array $scrapers = null;

    /** @return array<string, Scraper> key => bộ lấy dữ liệu */
    public function all(): array
    {
        return $this->scrapers ??= collect(self::SCRAPERS)
            ->map(fn (string $class) => app($class))
            ->merge(collect(Banks::ALL)->map(fn (array $bank) => new BankProfile($bank)))
            ->keyBy(fn (Scraper $scraper) => $scraper->key())
            ->all();
    }

    public function find(string $key): ?Scraper
    {
        return $this->all()[$key] ?? null;
    }

    public function for(SyncSource $source): ?Scraper
    {
        return $this->find($source->key);
    }

    /** Đồng bộ danh sách nguồn vào database. */
    public function sync(): void
    {
        foreach ($this->all() as $key => $scraper) {
            $source = SyncSource::query()->firstOrNew(['key' => $key]);
            if (! $source->exists) {
                $source->auto_approve = $scraper->kind()->autoApproveByDefault();
            }
            $source->fill(['kind' => $scraper->kind(), 'name' => $scraper->name(), 'url' => $scraper->url()])->save();
        }
    }
}
