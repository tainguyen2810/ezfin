<?php

namespace App\Modules\Content\Console;

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use App\Modules\Content\Support\StarterContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Nạp bộ bài viết kiến thức khởi đầu (nội dung thật của Ban biên tập, có nguồn), chạy được trên production.
 * Chạy lại an toàn: chuyên mục, tác giả, bài đã có (theo slug, kể cả bài trong thùng rác) được giữ nguyên.
 */
class StarterContentCommand extends Command
{
    protected $signature = 'ezfin:starter-content {--draft : Tạo bài ở trạng thái nháp để duyệt trước khi xuất bản}';

    protected $description = 'Nạp bộ bài viết kiến thức khởi đầu của Ban biên tập EzFin';

    private const COVERS = 'database/seeders/Demo/assets/covers';

    public function handle(): int
    {
        $categories = [];
        $sort = (int) ArticleCategory::max('sort_order');
        foreach (StarterContent::CATEGORIES as $slug => [$name, $description]) {
            $categories[$slug] = ArticleCategory::firstOrCreate(['slug' => $slug],
                ['name' => $name, 'description' => $description, 'sort_order' => ++$sort]);
        }

        $author = Author::firstOrCreate(['slug' => StarterContent::AUTHOR['slug']],
            [...collect(StarterContent::AUTHOR)->except('slug')->all(), 'is_active' => true]);

        $created = $skipped = 0;
        foreach (StarterContent::articles() as $i => $data) {
            if (Article::withTrashed()->where('slug', $data['slug'])->exists()) {
                $skipped++;

                continue;
            }

            $article = new Article([
                'category_id' => $categories[$data['category']]->id,
                'author_id' => $author->id,
                'cover_path' => $this->cover($i),
                ...collect($data)->only(['title', 'slug', 'excerpt', 'body', 'faq', 'references'])->all(),
            ]);
            $article->status = $this->option('draft') ? ArticleStatus::Draft : ArticleStatus::Published;
            $article->save();
            $created++;
        }

        $this->info("Đã tạo $created bài viết".($this->option('draft') ? ' (nháp)' : '').", bỏ qua $skipped bài đã có.");

        return self::SUCCESS;
    }

    private function cover(int $i): ?string
    {
        $source = base_path(self::COVERS.'/cover-'.($i % 12 + 1).'.jpg');
        if (! is_file($source)) {
            return null;
        }

        $path = 'articles/'.Str::lower(Str::random(12)).'.jpg';
        Storage::disk('public')->put($path, file_get_contents($source));

        return $path;
    }
}
