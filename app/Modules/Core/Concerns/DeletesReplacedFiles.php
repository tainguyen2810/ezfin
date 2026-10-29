<?php

namespace App\Modules\Core\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Xóa file trên ổ `public` khi trường đường dẫn file đổi giá trị hoặc bản ghi bị xóa,
 * để không còn file mồ côi. Model khai báo các trường trong fileAttributes().
 *
 * @mixin Model
 */
trait DeletesReplacedFiles
{
    public static function bootDeletesReplacedFiles(): void
    {
        static::updated(function (Model $model) {
            foreach ($model->fileAttributes() as $attribute) {
                if ($model->wasChanged($attribute)) {
                    self::deletePublicFile($model->getOriginal($attribute));
                }
            }
        });

        static::deleted(function (Model $model) {
            foreach ($model->fileAttributes() as $attribute) {
                self::deletePublicFile($model->getAttribute($attribute));
            }
        });
    }

    /** @return list<string> */
    abstract public function fileAttributes(): array;

    private static function deletePublicFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
