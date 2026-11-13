<?php

namespace App\Modules\Newsletter\Http\Controllers;

use App\Modules\Compare\Actions\BestSavingsRates;
use App\Modules\Newsletter\Actions\ConfirmSubscription;
use App\Modules\Newsletter\Actions\RequestSubscription;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController
{
    /** Thông báo giống nhau cho mọi trường hợp, không lộ email đã đăng ký hay chưa. */
    public const SENT_MESSAGE = 'Đã gửi email xác nhận. Hãy mở hộp thư (kể cả mục Spam/Quảng cáo) và bấm "Xác nhận đăng ký" để bắt đầu nhận tin.';

    public function store(Request $request, RequestSubscription $subscribe): RedirectResponse
    {
        $back = redirect()->to(url()->previous(route('home')).'#nhan-tin');

        // Ô ẩn chống bot: có giá trị thì giả vờ thành công, không gửi gì.
        if (filled($request->input('website'))) {
            return $back->with('newsletter', self::SENT_MESSAGE);
        }

        $data = $request->validateWithBag('newsletter', [
            'email' => ['required', 'string', 'max:191', 'email:rfc'],
            'ky_han' => ['nullable', 'integer', Rule::in(BestSavingsRates::TERMS)],
            'ban_tin' => ['nullable', 'boolean'],
            'dong_y' => ['accepted'],
        ], [
            'email.required' => 'Nhập email của bạn.',
            'email.email' => 'Email chưa đúng định dạng.',
            'dong_y.accepted' => 'Cần đồng ý để EzFin gửi email cho bạn.',
        ]);

        $term = filled($data['ky_han'] ?? null) ? (int) $data['ky_han'] : null;
        $weekly = (bool) ($data['ban_tin'] ?? false);
        if ($term === null && ! $weekly) {
            throw ValidationException::withMessages(['ky_han' => 'Chọn ít nhất một loại email muốn nhận.'])->errorBag('newsletter');
        }

        $referer = parse_url((string) url()->previous(), PHP_URL_PATH);
        $subscribe->handle($data['email'], $term, $weekly, is_string($referer) ? $referer : null);

        return $back->with('newsletter', self::SENT_MESSAGE);
    }

    /** Trang xác nhận: cần bấm nút (POST) để trình quét link trong hộp thư không tự xác nhận thay người dùng. */
    public function confirmForm(string $payload, ConfirmSubscription $confirm): View
    {
        return view('newsletter::pages.confirm', ['request' => $confirm->read($payload), 'payload' => $payload]);
    }

    public function confirm(string $payload, ConfirmSubscription $confirm): RedirectResponse|View
    {
        $request = $confirm->read($payload);
        if ($request === null) {
            return view('newsletter::pages.confirm', ['request' => null, 'payload' => $payload]);
        }

        $subscriber = $confirm->handle($request);

        return redirect()->route('newsletter.manage', $subscriber->token)->with('newsletter', 'Đã xác nhận. Bạn có thể đổi lựa chọn hoặc hủy nhận bất kỳ lúc nào ở trang này hoặc từ link cuối mỗi email.');
    }

    public function manage(string $token): View
    {
        $subscriber = Subscriber::query()->where('token', $token)->with('alerts')->first();

        return view('newsletter::pages.manage', ['subscriber' => $subscriber, 'token' => $token]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();
        $data = $request->validate([
            'ban_tin' => ['nullable', 'boolean'],
            'ky_han' => ['nullable', 'array'],
            'ky_han.*' => ['integer', Rule::in(BestSavingsRates::TERMS)],
        ]);
        $terms = array_map('intval', $data['ky_han'] ?? []);

        $deleted = DB::transaction(function () use ($subscriber, $data, $terms) {
            $subscriber->update(['weekly_digest' => (bool) ($data['ban_tin'] ?? false)]);
            $subscriber->alerts()->whereNotIn('term_months', $terms)->delete();

            $current = app(BestSavingsRates::class)->topByTerm($terms);
            foreach ($terms as $term) {
                $subscriber->alerts()->firstOrCreate(['term_months' => $term], ['baseline_rate' => $current[$term]['rate'] ?? null]);
            }

            return $subscriber->deleteIfEmpty();
        });

        return redirect()->route('newsletter.manage', $token)
            ->with('newsletter', $deleted ? 'Bạn đã hủy nhận toàn bộ email. EzFin đã xóa địa chỉ email của bạn.' : 'Đã lưu lựa chọn.');
    }

    /** Hủy toàn bộ: nút trên trang quản lý, và hủy một chạm từ hộp thư (List-Unsubscribe-Post, không có CSRF token). */
    public function unsubscribe(string $token): Response|RedirectResponse
    {
        Subscriber::query()->where('token', $token)->delete();

        if (request()->input('List-Unsubscribe') === 'One-Click') {
            return response('Đã hủy nhận email.', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return redirect()->route('newsletter.manage', $token)->with('newsletter', 'Bạn đã hủy nhận toàn bộ email. EzFin đã xóa địa chỉ email của bạn.');
    }

    public static function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($name, 0, 2).str_repeat('•', max(1, Str::length($name) - 2)).'@'.$domain;
    }
}
