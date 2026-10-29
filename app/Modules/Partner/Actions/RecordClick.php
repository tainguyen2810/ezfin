<?php

namespace App\Modules\Partner\Actions;

use App\Modules\Partner\Models\OutboundClick;
use App\Modules\Partner\Models\OutboundLink;
use App\Modules\Partner\Support\BotDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Ghi một click sang đối tác. Không ghi khi là bot, khi không phải GET, hoặc khi cùng IP bấm cùng link
 * quá MAX_PER_MINUTE lần một phút (tránh thổi phồng số liệu). Trả mã click, hoặc null nếu không ghi.
 */
class RecordClick
{
    public const MAX_PER_MINUTE = 5;

    public function handle(OutboundLink $link, Request $request): ?string
    {
        $userAgent = $request->userAgent();

        if (! $request->isMethod('GET') || BotDetector::isBot($userAgent)) {
            return null;
        }

        $ipHash = $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null;

        if (! RateLimiter::attempt('outbound-click:'.$link->id.':'.$ipHash, self::MAX_PER_MINUTE, fn () => true)) {
            return null;
        }

        $click = OutboundClick::create([
            'link_id' => $link->id,
            'click_id' => Str::random(24),
            'ip_hash' => $ipHash,
            'user_agent' => Str::limit((string) $userAgent, 252),
            'referer_path' => $this->refererPath($request),
            'created_at' => now(),
        ]);

        return $click->click_id;
    }

    /** Chỉ giữ đường dẫn trang EzFin dẫn tới click (không giữ tham số, không giữ trang ngoài). */
    private function refererPath(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer');
        $host = parse_url($referer, PHP_URL_HOST);

        if (! $host || ! in_array($host, [config('ezfin.domains.public'), config('ezfin.domains.app')], true)) {
            return null;
        }

        return Str::limit(parse_url($referer, PHP_URL_PATH) ?: '/', 497);
    }
}
