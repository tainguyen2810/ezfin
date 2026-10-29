<?php

namespace App\Support\Seo;

use DateTimeInterface;

final readonly class SitemapUrl
{
    public function __construct(
        public string $loc,
        public ?DateTimeInterface $lastmod = null,
    ) {}
}
