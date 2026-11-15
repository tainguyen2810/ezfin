<?php

namespace App\Modules\DataSync\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * HTTP cho bộ lấy dữ liệu: user-agent chung của EzFin (không có thông tin cá nhân), tôn trọng robots.txt,
 * nghỉ giữa hai lần gọi cùng một máy chủ, báo lỗi dễ hiểu (bị chặn, hết thời gian, máy chủ lỗi).
 */
class Fetcher
{
    /** @var array<string, float> host => thời điểm gọi gần nhất (microtime) */
    private array $lastRequestAt = [];

    public function __construct(private readonly RobotsTxt $robots) {}

    public static function userAgent(): string
    {
        return 'EzFinBot/1.0 (+'.rtrim((string) config('app.url'), '/').')';
    }

    public function get(string $url, array $query = []): Response
    {
        return $this->send('GET', $url, $query === [] ? [] : ['query' => $query]);
    }

    public function post(string $url, array $form = []): Response
    {
        return $this->send('POST', $url, ['form_params' => $form]);
    }

    public function html(string $url, array $query = []): string
    {
        return self::nfc($this->get($url, $query)->body());
    }

    /**
     * Chữ tiếng Việt về dạng dựng sẵn (NFC). Một số trang ghi dấu bằng ký tự tổ hợp ("I" + dấu huyền),
     * nhìn giống hệt nhưng so chuỗi "điện tử" sẽ không khớp.
     */
    public static function nfc(string $text): string
    {
        if ($text === '' || ! class_exists(\Normalizer::class) || \Normalizer::isNormalized($text)) {
            return $text;
        }

        return \Normalizer::normalize($text) ?: $text;
    }

    public function json(string $url, array $query = []): array
    {
        return $this->decode($this->get($url, $query), $url);
    }

    public function decode(Response $response, string $url): array
    {
        $data = json_decode(self::nfc($response->body()), true);
        if (! is_array($data)) {
            throw ScrapeException::layoutChanged("{$url} không trả về JSON.");
        }

        return $data;
    }

    private function send(string $method, string $url, array $options): Response
    {
        $target = $url.(isset($options['query']) ? '?'.http_build_query($options['query']) : '');
        if (! $this->robots->allows($target)) {
            throw new ScrapeException("robots.txt của nguồn không cho phép truy cập {$url}. Đã dừng, không lấy dữ liệu.");
        }

        $this->pause((string) parse_url($url, PHP_URL_HOST));

        try {
            $response = $this->client()->send($method, $url, $options);
        } catch (ConnectionException $e) {
            throw new ScrapeException('Không kết nối được tới nguồn: '.Str::limit($e->getMessage(), 200));
        }

        if (in_array($response->status(), [401, 403, 429], true)) {
            throw new ScrapeException("Nguồn từ chối truy cập (mã {$response->status()}), có thể đã chặn bot hoặc giới hạn tần suất.");
        }
        if (! $response->successful()) {
            throw new ScrapeException("Nguồn trả lỗi mã {$response->status()} cho {$url}.");
        }

        return $response;
    }

    private function client(): PendingRequest
    {
        return Http::withUserAgent(self::userAgent())
            ->withHeaders(['Accept-Language' => 'vi-VN,vi;q=0.9'])
            ->timeout((int) config('ezfin.data_sync.timeout', 20))
            ->retry(2, 2000, fn (\Throwable $e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()), throw: false);
    }

    /** Giãn cách các lần gọi cùng một máy chủ để không gây tải cho nguồn. */
    private function pause(string $host): void
    {
        $delay = (int) config('ezfin.data_sync.delay_ms', 1500) / 1000;
        $last = $this->lastRequestAt[$host] ?? null;

        if ($last !== null && $delay > 0 && ($wait = $delay - (microtime(true) - $last)) > 0) {
            usleep((int) ($wait * 1_000_000));
        }

        $this->lastRequestAt[$host] = microtime(true);
    }
}
