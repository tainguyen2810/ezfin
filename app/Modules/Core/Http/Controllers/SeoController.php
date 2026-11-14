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
            ? ['User-agent: *', 'Disallow: /di/', 'Disallow: /postback/', 'Disallow: /_ui', 'Disallow: /'.config('ezfin.app_prefix').'/', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return $this->text($lines);
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
