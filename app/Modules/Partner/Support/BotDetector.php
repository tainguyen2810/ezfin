<?php

namespace App\Modules\Partner\Support;

/** Lọc bot cơ bản theo User-Agent: không ghi click của bot, trình xem trước link, công cụ dòng lệnh. */
class BotDetector
{
    private const PATTERN = '/bot|crawl|spider|slurp|archiver|facebookexternalhit|embedly|preview|whatsapp|telegram|zalo|skype|discord|slack|'
        .'headless|phantomjs|lighthouse|pagespeed|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|httpclient|guzzle|scrapy|postman|monitor|uptime|check/i';

    public static function isBot(?string $userAgent): bool
    {
        $userAgent = trim((string) $userAgent);

        return $userAgent === '' || ! str_starts_with($userAgent, 'Mozilla/') || preg_match(self::PATTERN, $userAgent) === 1;
    }
}
