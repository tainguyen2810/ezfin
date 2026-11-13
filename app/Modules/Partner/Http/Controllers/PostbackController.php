<?php

namespace App\Modules\Partner\Http\Controllers;

use App\Modules\Partner\Actions\RecordConversion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * ezfin.vn/postback/{mạng}?token=...&click_id=...&transaction_id=...&status=...&commission=...&time=...
 * Mạng affiliate gọi khi có chuyển đổi hoặc đổi trạng thái. Tắt (404) khi chưa đặt EZFIN_POSTBACK_TOKEN.
 */
class PostbackController
{
    public function __invoke(Request $request, string $network, RecordConversion $record): Response
    {
        $secret = (string) config('ezfin.partner.postback_token');
        abort_if($secret === '', 404);

        if (! hash_equals($secret, (string) $request->query('token'))) {
            return $this->text('Sai token', 403);
        }

        try {
            $result = $record->handle($network, [
                'click_id' => $request->query('click_id', $request->query('sub_id')),
                'transaction_id' => $request->query('transaction_id', $request->query('order_id')),
                'status' => $request->query('status'),
                'commission' => $request->query('commission', $request->query('payout')),
                'occurred_at' => $request->query('time'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->text($exception->getMessage(), 422);
        }

        return $this->text($result['created'] ? 'OK: đã ghi' : 'OK: đã cập nhật');
    }

    private function text(string $message, int $status = 200): Response
    {
        return response($message."\n", $status)->withHeaders([
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
