<?php

namespace App\Modules\Core\Notifications;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Nội dung đã thay biến của một thông báo trên một kênh. Thân email là Markdown đơn giản (HTML thô bị thoát). */
final class RenderedMessage
{
    public function __construct(
        public readonly string $key,
        public readonly Channel $channel,
        public readonly string $subject,
        public readonly string $greeting,
        public readonly string $body,
        public readonly string $actionLabel,
    ) {}

    public function bodyHtml(): HtmlString
    {
        return new HtmlString(trim(Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false])));
    }
}
