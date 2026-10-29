<?php

namespace App\Modules\Core\Actions;

use App\Modules\Core\Models\Redirect;

/**
 * Ghi chuyển hướng 301 khi URL công khai đổi (đổi slug, đổi chuyên mục).
 * Gộp chuỗi chuyển hướng (A → B rồi B → C thành A → C) và bỏ chuyển hướng đang chặn URL mới.
 */
class RecordRedirect
{
    public function handle(string $from, string $to): void
    {
        $from = Redirect::normalizePath($from);
        $to = Redirect::normalizePath($to);

        if ($from === $to) {
            return;
        }

        // URL mới đang có trang: chuyển hướng cũ từ URL này không còn đúng.
        Redirect::where('from_path', $to)->delete();

        Redirect::where('to_url', $from)->each(fn (Redirect $redirect) => $redirect->update(['to_url' => $to]));

        Redirect::updateOrCreate(['from_path' => $from], ['to_url' => $to, 'status_code' => 301]);
    }
}
