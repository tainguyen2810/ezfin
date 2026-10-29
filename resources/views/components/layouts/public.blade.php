{{-- Khung website công khai (ezfin.vn). $navigation: ghi đè menu (chỉ dùng trong trang mẫu).
     canonical: URL chuẩn (mặc định URL hiện tại không kèm tham số); slot head: thẻ thêm trong <head> (ví dụ JSON-LD).
     seo: SeoMeta do biên tập viên nhập, ghi đè tiêu đề, mô tả, ảnh chia sẻ, noindex.
     image: ảnh chia sẻ của trang (ví dụ ảnh bìa bài viết); type: og:type (website, article). --}}
@props(['title' => null, 'description' => null, 'navigation' => null, 'noindex' => false, 'canonical' => null,
    'seo' => null, 'image' => null, 'type' => 'website'])
@php
    $siteName = 'EzFin';
    $pageTitle = $seo?->title ?: ($title ? $title.' | '.$siteName : 'EzFin — Tài chính thông minh cho cuộc sống tốt hơn');
    $metaDescription = $seo?->description ?: ($description ?: 'So sánh lãi suất tiết kiệm, thẻ tín dụng, tài khoản ngân hàng và công cụ tính tài chính cá nhân cho người Việt. Dữ liệu có nguồn và ngày xác minh.');
    // Ngoài production (staging, dev) mọi trang đều noindex.
    $noindex = $noindex || ($seo?->noindex ?? false) || ! config('ezfin.indexable');
    $canonical ??= url()->current();
    $shareImage = url($seo?->ogImageUrl() ?? $image ?? asset('images/og-default.png'));

    $navItems = $navigation ?? app(\App\Support\Navigation\Navigation::class)->visible()
        ->map(fn ($item) => ['label' => $item->label, 'href' => route($item->route), 'active' => request()->routeIs($item->route.'*')])
        ->all();
    $footerLinks = collect(\App\Modules\Core\CoreServiceProvider::LEGAL_PAGES)->filter(fn ($label, $route) => Route::has($route));
    $company = array_filter(config('ezfin.company', []), 'filled');
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $seo?->title ?: ($title ?? $pageTitle) }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta name="twitter:card" content="summary_large_image">
    @isset($head){{ $head }}@endisset
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#noi-dung" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Bỏ qua tới nội dung</a>

    <header x-data="{ menu: false }" class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6">
            <a href="{{ Route::has('home') ? route('home') : '/' }}" aria-label="EzFin trang chủ"><x-ui.logo /></a>
            @if ($navItems)
                <nav class="hidden flex-1 items-center gap-1 lg:flex" aria-label="Menu chính">
                    @foreach ($navItems as $item)
                        <a href="{{ $item['href'] }}" @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'text-primary-700 bg-primary-50' => $item['active'] ?? false,
                            'text-navy-900 hover:bg-slate-100' => ! ($item['active'] ?? false),
                        ]) @if ($item['active'] ?? false) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
                <button type="button" class="ml-auto rounded-lg p-2 text-navy-900 hover:bg-slate-100 lg:hidden" x-on:click="menu = !menu" x-bind:aria-expanded="menu" aria-controls="menu-mobile">
                    <span x-show="!menu"><x-ui.icon name="bars-3" class="size-6" /></span><span x-show="menu" x-cloak><x-ui.icon name="x-mark" class="size-6" /></span>
                    <span class="sr-only">Mở menu</span>
                </button>
            @endif
            @isset($actions)<div class="hidden items-center gap-2 lg:flex">{{ $actions }}</div>@endisset
        </div>
        @if ($navItems)
            <nav id="menu-mobile" x-show="menu" x-cloak x-transition class="border-t border-slate-200 bg-white px-4 py-3 lg:hidden" aria-label="Menu chính">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="flex items-center justify-between rounded-lg px-3 py-3 text-base font-medium text-navy-900 hover:bg-slate-50">
                        {{ $item['label'] }} <x-ui.icon name="chevron-right" class="size-4 text-slate-400" />
                    </a>
                @endforeach
                @isset($actions)<div class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3">{{ $actions }}</div>@endisset
            </nav>
        @endif
    </header>

    <main id="noi-dung" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-10 sm:px-6 md:grid-cols-[2fr_1fr_1fr]">
            <div class="col-span-2 md:col-span-1">
                <x-ui.logo />
                <p class="mt-3 max-w-sm text-sm text-slate-500">Tài chính thông minh cho cuộc sống tốt hơn. So sánh sản phẩm tài chính, công cụ tính toán và quản lý tiền cho người Việt.</p>
                @if ($company)
                    <address class="mt-4 space-y-1 text-sm not-italic text-slate-500">
                        @isset($company['name'])<p class="font-medium text-navy-900">{{ $company['name'] }}</p>@endisset
                        @isset($company['registration'])<p>{{ $company['registration'] }}</p>@endisset
                        @isset($company['address'])<p>{{ $company['address'] }}</p>@endisset
                        @isset($company['email'])<p><a href="mailto:{{ $company['email'] }}" class="hover:text-primary-700">{{ $company['email'] }}</a></p>@endisset
                        @isset($company['phone'])<p><a href="tel:{{ preg_replace('/[^\d+]/', '', $company['phone']) }}" class="hover:text-primary-700">{{ $company['phone'] }}</a></p>@endisset
                    </address>
                @endif
            </div>
            @if ($navItems)
                <nav aria-label="Khám phá">
                    <h2 class="text-sm font-semibold text-navy-900">Khám phá</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($navItems as $item)
                            <li><a href="{{ $item['href'] }}" class="text-slate-600 hover:text-primary-700">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
            @if ($footerLinks->isNotEmpty())
                <nav aria-label="Thông tin">
                    <h2 class="text-sm font-semibold text-navy-900">Về EzFin</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($footerLinks as $route => $label)
                            <li><a href="{{ route($route) }}" class="text-slate-600 hover:text-primary-700">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
        <div class="border-t border-slate-100">
            <p class="mx-auto max-w-7xl px-4 py-4 text-xs text-slate-500 sm:px-6">
                © {{ now()->year }} EzFin. Thông tin trên EzFin chỉ mang tính tham khảo, không phải lời khuyên đầu tư hay cam kết của tổ chức tài chính. Lãi suất, phí và điều kiện có thể thay đổi; vui lòng xác nhận với tổ chức phát hành trước khi quyết định.
            </p>
        </div>
    </footer>

    <x-ui.toasts />
</body>
</html>
