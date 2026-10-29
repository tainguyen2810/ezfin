{{-- Khung website công khai (ezfin.vn). $navigation: ghi đè menu (chỉ dùng trong trang mẫu). --}}
@props(['title' => null, 'description' => null, 'navigation' => null, 'noindex' => false])
@php
    $navItems = $navigation ?? app(\App\Support\Navigation\Navigation::class)->visible()
        ->map(fn ($item) => ['label' => $item->label, 'href' => route($item->route), 'active' => request()->routeIs($item->route.'*')])
        ->all();
    $footerLinks = collect([
        'legal.about' => 'Giới thiệu',
        'legal.methodology' => 'Phương pháp đánh giá',
        'legal.disclosure' => 'Công bố liên kết',
        'legal.terms' => 'Điều khoản sử dụng',
        'legal.privacy' => 'Quyền riêng tư',
        'legal.contact' => 'Liên hệ',
    ])->filter(fn ($label, $route) => Route::has($route));
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | EzFin' : 'EzFin — Tài chính thông minh cho cuộc sống tốt hơn' }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
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
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[2fr_3fr]">
            <div>
                <x-ui.logo />
                <p class="mt-3 max-w-sm text-sm text-slate-500">Tài chính thông minh cho cuộc sống tốt hơn. So sánh sản phẩm tài chính, công cụ tính toán và quản lý tiền cho người Việt.</p>
            </div>
            @if ($footerLinks->isNotEmpty())
                <nav class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3" aria-label="Thông tin">
                    @foreach ($footerLinks as $route => $label)
                        <a href="{{ route($route) }}" class="text-slate-600 hover:text-primary-700">{{ $label }}</a>
                    @endforeach
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
