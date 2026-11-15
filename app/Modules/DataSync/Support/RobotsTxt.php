<?php

namespace App\Modules\DataSync\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kiểm tra robots.txt trước khi lấy dữ liệu. Dùng nhóm luật dành riêng cho bot EzFin nếu có, nếu không dùng nhóm "*".
 * Luật dài nhất khớp được áp dụng; Allow thắng khi bằng nhau. Không có robots.txt (lỗi, 404, trả về trang HTML) = được phép.
 */
class RobotsTxt
{
    public const BOT_TOKEN = 'ezfinbot';

    public const CACHE_HOURS = 24;

    public function allows(string $url): bool
    {
        $parts = parse_url($url);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        $rules = Cache::remember('data-sync:robots:'.$origin, now()->addHours(self::CACHE_HOURS), fn () => $this->fetch($origin));

        return self::evaluate($rules, $path);
    }

    /** @return list<array{bool, string}> [allow, pattern] */
    private function fetch(string $origin): array
    {
        try {
            $response = Http::withUserAgent(Fetcher::userAgent())->timeout(10)->get($origin.'/robots.txt');
        } catch (Throwable) {
            return [];
        }

        $body = $response->body();
        if (! $response->successful() || preg_match('/<\s*(html|!doctype)/i', $body)) {
            return [];
        }

        return self::parse($body);
    }

    /** @return list<array{bool, string}> */
    public static function parse(string $content): array
    {
        $groups = [];
        $agents = [];
        $inRules = false;

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if (! str_contains($line, ':')) {
                continue;
            }
            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if ($inRules) {
                    $agents = [];
                    $inRules = false;
                }
                $agents[] = strtolower($value);

                continue;
            }

            if (in_array($field, ['allow', 'disallow'], true)) {
                $inRules = true;
                foreach ($agents as $agent) {
                    $groups[$agent] ??= [];
                    if ($value !== '') {
                        $groups[$agent][] = [$field === 'allow', $value];
                    }
                }
            }
        }

        foreach (array_keys($groups) as $agent) {
            if ($agent !== '*' && str_contains(self::BOT_TOKEN, $agent)) {
                return $groups[$agent];
            }
        }

        return $groups['*'] ?? [];
    }

    /** @param  list<array{bool, string}>  $rules */
    public static function evaluate(array $rules, string $path): bool
    {
        $best = null;

        foreach ($rules as [$allow, $pattern]) {
            $regex = '#^'.str_replace(['\*', '\$'], ['.*', '$'], preg_quote($pattern, '#')).'#';
            if (preg_match($regex, $path)) {
                $length = strlen($pattern);
                if ($best === null || $length > $best[1] || ($length === $best[1] && $allow)) {
                    $best = [$allow, $length];
                }
            }
        }

        return $best === null || $best[0];
    }
}
