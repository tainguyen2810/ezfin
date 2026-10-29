<?php

namespace App\Modules\Core\Http\Controllers;

use App\Support\Seo\Sitemap;
use Illuminate\Http\Response;

class SeoController
{
    /** robots.txt của website công khai. Ngoài production chặn toàn bộ. */
    public function robots(): Response
    {
        $lines = config('ezfin.indexable')
            ? ['User-agent: *', 'Disallow: /di/', 'Disallow: /_ui', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return $this->text($lines);
    }

    /** Ứng dụng khách hàng và trang quản trị không bao giờ được lập chỉ mục. */
    public function appRobots(): Response
    {
        return $this->text(['User-agent: *', 'Disallow: /']);
    }

    public function sitemap(Sitemap $sitemap): Response
    {
        return response()
            ->view('core::seo.sitemap', ['urls' => $sitemap->urls()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** @param  list<string>  $lines */
    private function text(array $lines): Response
    {
        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
