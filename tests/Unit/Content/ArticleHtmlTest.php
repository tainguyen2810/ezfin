<?php

use App\Modules\Content\Support\ArticleHtml;

it('removes scripts, event handlers, iframes, styles and dangerous links', function (string $dirty, string $mustNotContain) {
    expect(ArticleHtml::sanitize($dirty) ?? '')->not->toContain($mustNotContain);
})->with([
    'script tag' => ['<p>An toàn</p><script>alert(1)</script>', '<script'],
    'event handler' => ['<img src="/storage/a.png" onerror="alert(1)">', 'onerror'],
    'javascript link' => ['<a href="javascript:alert(1)">bấm</a>', 'javascript:'],
    'encoded javascript link' => ['<a href="jav&#x09;ascript:alert(1)">bấm</a>', 'ascript:'],
    'data link' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">bấm</a>', 'data:'],
    'iframe' => ['<iframe src="https://example.com"></iframe>', '<iframe'],
    'inline style' => ['<p style="background:url(javascript:alert(1))">x</p>', 'style'],
    'svg' => ['<svg onload="alert(1)"><circle /></svg>', '<svg'],
    'form' => ['<form action="https://example.com"><input name="pin"></form>', '<input'],
    'http image' => ['<img src="http://example.com/a.png">', 'http://example.com'],
    'h1' => ['<h1>Tiêu đề thứ hai</h1>', '<h1'],
]);

it('keeps the formatting the editor produces', function () {
    $html = '<h2>Lãi kép</h2><p><strong>Đậm</strong> và <em>nghiêng</em>, <a href="https://sbv.gov.vn">nguồn</a>, <a href="/cong-cu/lai-kep">công cụ</a>.</p>'
        .'<ul><li>Một</li></ul><ol start="2"><li>Hai</li></ol><blockquote><p>Trích dẫn</p></blockquote>'
        .'<table><tbody><tr><th colspan="2"><p>Kỳ hạn</p></th></tr><tr><td><p>6 tháng</p></td><td><p>5%</p></td></tr></tbody></table>'
        .'<img src="/storage/articles/noi-dung/a.png" alt="Biểu đồ" data-id="articles/noi-dung/a.png" />';

    expect(ArticleHtml::sanitize($html))->toBe($html);
});

it('treats an empty editor as no content', function () {
    expect(ArticleHtml::sanitize('<p></p>'))->toBeNull()
        ->and(ArticleHtml::sanitize('  '))->toBeNull()
        ->and(ArticleHtml::text('<p>Một&nbsp;hai</p><p>ba</p>'))->toBe('Một hai ba');
});

it('estimates reading time at 200 words a minute', function () {
    expect(ArticleHtml::readingMinutes('<p>'.str_repeat('từ ', 401).'</p>'))->toBe(3)
        ->and(ArticleHtml::readingMinutes(null))->toBe(1);
});

it('accepts only http and https urls for links printed outside the body', function () {
    expect(ArticleHtml::safeUrl('https://sbv.gov.vn/a'))->toBe('https://sbv.gov.vn/a')
        ->and(ArticleHtml::safeUrl('javascript:alert(1)'))->toBeNull()
        ->and(ArticleHtml::safeUrl('ftp://example.com'))->toBeNull()
        ->and(ArticleHtml::safeUrl(''))->toBeNull();
});

it('keeps the formatting TinyMCE produces: colours, alignment, font size, image size, tables, link targets', function () {
    $html = '<p style="text-align: center;"><span style="color: #e03e2d; background-color: rgb(251, 238, 184); font-size: 18px;">Lãi suất</span></p>'
        .'<h4>Mục nhỏ</h4><p><a href="/lai-suat-tiet-kiem" target="_blank" rel="noopener">Xem</a></p>'
        .'<p><img src="/storage/articles/noi-dung/a.jpg" alt="Ảnh" width="600" height="400" style="float: right; margin-left: 10px;"></p>'
        .'<table style="border-collapse: collapse; width: 100%;" border="1"><tbody><tr><td style="width: 50%; text-align: right;">1</td></tr></tbody></table>';

    $clean = ArticleHtml::sanitize($html);

    expect($clean)->toContain('style="text-align: center"')
        ->toContain('color: #e03e2d; background-color: rgb(251, 238, 184); font-size: 18px')
        ->toContain('<h4>Mục nhỏ</h4>')
        ->toContain('target="_blank"')
        ->toContain('style="float: right; margin-left: 10px"')
        ->toContain('style="border-collapse: collapse; width: 100%"')
        ->toContain('style="width: 50%; text-align: right"');
});

it('drops dangerous or layout-breaking CSS from style attributes', function (string $style) {
    expect(ArticleHtml::sanitize('<p style="'.$style.'">x</p>'))->toBe('<p>x</p>');
})->with([
    'url()' => ['background-image: url(https://evil.example/t.gif)'],
    'expression' => ['width: expression(alert(1))'],
    'position fixed' => ['position: fixed; top: 0'],
    'javascript' => ['color: javascript:alert(1)'],
    'escaped url' => ['background: u\\72l(x)'],
    'display none' => ['display: none'],
]);
