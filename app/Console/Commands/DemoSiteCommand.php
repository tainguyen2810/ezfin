<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\SeoMeta;
use App\Modules\Partner\Models\OutboundLink;
use App\Modules\Personal\Actions\DeleteAccount;
use App\Modules\Personal\Console\DemoDataCommand;
use App\Support\Demo\DemoManifest;
use Database\Seeders\Demo\DemoArticles;
use Database\Seeders\Demo\DemoCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

/**
 * Bộ dữ liệu demo cho toàn website để duyệt trải nghiệm: ngân hàng, lãi suất, thẻ, tài khoản, link đối tác,
 * bài viết, tác giả và tài khoản app quản lý tiền. Mọi bản ghi được ghi vào DemoManifest để `--purge` xóa đúng phần đã tạo.
 */
class DemoSiteCommand extends Command
{
    protected $signature = 'ezfin:demo {--purge : Xóa toàn bộ dữ liệu demo đã tạo} {--fresh : Xóa dữ liệu demo cũ rồi tạo lại}';

    protected $description = 'Tạo (hoặc xóa) dữ liệu demo cho mọi chức năng của EzFin';

    /** Thứ tự xóa: bảng con trước bảng cha. */
    private const PURGE_ORDER = [OutboundLink::class, FinancialProduct::class, FinancialInstitution::class, Article::class, Author::class, ArticleCategory::class];

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Không tạo hay xóa dữ liệu demo trên production.');

            return self::FAILURE;
        }

        if ($this->option('purge')) {
            $this->purge();

            return self::SUCCESS;
        }

        if (DemoManifest::active()) {
            if (! $this->option('fresh')) {
                $this->error('Đã có dữ liệu demo. Dùng --fresh để tạo lại hoặc --purge để xóa.');

                return self::FAILURE;
            }
            $this->purge();
        }

        $manifest = new DemoManifest;
        try {
            [$catalog, $articles] = DB::transaction(fn () => [(new DemoCatalog($manifest))->run(), (new DemoArticles($manifest))->run()]);
        } catch (\Throwable $e) {
            foreach ($manifest->files() as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
        $manifest->trackUser(DemoDataCommand::DEMO_EMAIL);
        $manifest->save();

        foreach (['compare', 'planner', 'content', 'partner', 'personal'] as $feature) {
            Feature::activate($feature);
        }

        // Chạy cuối vì lệnh này đăng nhập người dùng demo trong tiến trình console.
        $exists = User::where('email', DemoDataCommand::DEMO_EMAIL)->exists();
        Artisan::call('ezfin:demo-data', $exists ? ['--fresh' => true, '--no-interaction' => true] : ['--no-interaction' => true]);
        $personal = trim(Artisan::output());
        Auth::forgetUser();

        $this->info('Đã tạo dữ liệu demo:');
        $this->line("- {$catalog['institutions']} ngân hàng, công ty tài chính; {$catalog['savings']} sản phẩm tiết kiệm; {$catalog['cards']} thẻ tín dụng; {$catalog['accounts']} tài khoản thanh toán");
        $this->line("- {$catalog['links']} link đối tác kèm lượt bấm 60 ngày; $articles bài viết, 3 tác giả");
        $this->line('- '.$personal);
        $this->line('Đã bật các mục: so sánh, công cụ tính, kiến thức, đối tác, app quản lý tiền.');
        $this->line('Xóa sạch khi không cần: php artisan ezfin:demo --purge');

        return self::SUCCESS;
    }

    private function purge(): void
    {
        $manifest = DemoManifest::load();

        DB::transaction(function () use ($manifest) {
            foreach (self::PURGE_ORDER as $class) {
                $ids = $manifest->ids($class);
                if ($ids === []) {
                    continue;
                }
                $query = $class::query()->whereKey($ids);
                $class === Article::class ? $query->withTrashed()->forceDelete() : $query->delete();
                AuditLog::where('auditable_type', (new $class)->getMorphClass())->whereIn('auditable_id', $ids)->delete();
                SeoMeta::where('seoable_type', (new $class)->getMorphClass())->whereIn('seoable_id', $ids)->delete();
            }
        });

        foreach ($manifest->files() as $path) {
            Storage::disk('public')->delete($path);
        }

        $deleted = 0;
        foreach ($manifest->users() as $email) {
            $user = User::where('email', $email)->first();
            if ($user && ! app(DeleteAccount::class)->isStaff($user)) {
                app(DeleteAccount::class)->handle($user);
                $deleted++;
            }
        }

        DemoManifest::forget();
        $this->info('Đã xóa dữ liệu demo'.($deleted ? ' và tài khoản '.DemoDataCommand::DEMO_EMAIL : '').'.');
    }
}
