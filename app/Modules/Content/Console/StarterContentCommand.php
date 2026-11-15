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
    protected $signature = 'ezfin:starter-content
        {--draft : Tạo bài ở trạng thái nháp để duyệt trước khi xuất bản}
        {--refresh-covers : Thay ảnh bìa mặc định cũ (màu xanh rêu) bằng ảnh bìa mặc định hiện tại, giữ ảnh tự tải lên}';

    protected $description = 'Nạp bộ bài viết kiến thức khởi đầu của Ban biên tập EzFin';

    private const COVERS = 'database/seeders/Demo/assets/covers';

    /** MD5 của 12 ảnh bìa mặc định màu xanh rêu (trước 15/11/2026), theo thứ tự cover-1..12. */
    private const OLD_COVERS = [
        '78bae6f22fc4d2ff79c06c640debf364', '46d14afe39d0d0bceae2c30a1378b06f', 'e8573ce1678fd78211856fc6e02420c6',
        '5a3fb8e9f4117321cd6eb6a59f434c48', '272ef512e5d45ed0d311ae40c8a230bf', 'b0dbb31c1c9a9fc6a99be1134977ecc5',
        'b0b876f24fb9ccc49b3a1a6f13d9b3b0', '76970e53414c9ab0c55841dc9e4f15b8', 'f083f5fd942dc144be767e45036e8539',
        '308303244288b518f0fafbd418bde911', 'dc4aeaed2514dbc0df19ac4b3330ee46', '3ed8530949359642df88aa1f59b9af56',
    ];

    public function handle(): int
    {
        if ($this->option('refresh-covers')) {
            return $this->refreshCovers();
        }

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

    /**
     * Đổi màu thương hiệu: bài nào còn dùng ảnh bìa mặc định cũ thì nhận ảnh mặc định mới cùng số thứ tự.
     * Lưu thành file mới (tên khác) để trình duyệt và máy chủ không dùng lại ảnh cũ trong bộ nhớ đệm; rồi xóa file cũ.
     */
    private function refreshCovers(): int
    {
        $disk = Storage::disk('public');
        $updated = 0;

        Article::withTrashed()->whereNotNull('cover_path')->each(function (Article $article) use ($disk, &$updated) {
            if (! $disk->exists($article->cover_path)) {
                return;
            }

            $index = array_search(md5($disk->get($article->cover_path)), self::OLD_COVERS, true);
            if ($index === false || ! ($path = $this->cover($index))) {
                return;
            }

            $old = $article->cover_path;
            $article->cover_path = $path;
            $article->saveQuietly();
            $disk->delete($old);
            $updated++;
        });

        $this->info("Đã thay ảnh bìa cho $updated bài viết.");

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
