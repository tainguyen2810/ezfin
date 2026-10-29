<?php

namespace App\Modules\Partner\Http\Controllers;

use App\Modules\Partner\Actions\RecordClick;
use App\Modules\Partner\Models\OutboundLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** ezfin.vn/di/{code}: ghi click rồi chuyển sang đối tác. Không bao giờ được lập chỉ mục hay lưu cache. */
class OutboundController
{
    public function __invoke(Request $request, string $code, RecordClick $record): RedirectResponse
    {
        $link = OutboundLink::query()->active()->where('code', $code)->firstOrFail();

        return redirect()->away($link->destinationFor($record->handle($link, $request)), 302)
            ->withHeaders([
                'X-Robots-Tag' => 'noindex, nofollow',
                'Cache-Control' => 'no-store, private',
                'Referrer-Policy' => 'origin',
            ]);
    }
}
