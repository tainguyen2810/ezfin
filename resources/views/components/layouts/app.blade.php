{{-- Khung ứng dụng khách hàng (app.ezfin.vn): sidebar trên desktop, thanh điều hướng dưới + nút thêm trên mobile.
     $menu: [['label', 'icon', 'href', 'active']]; $bottom: tối đa 4 mục cho thanh dưới; $addUrl: nút + ở giữa; $userUrl: avatar dẫn tới trang tài khoản. --}}
@props(['title', 'menu' => [], 'bottom' => [], 'addUrl' => null, 'addLabel' => 'Thêm giao dịch', 'user' => null, 'userUrl' => null])
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | EzFin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex">
        <div class="flex h-16 items-center px-5"><x-ui.logo /></div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Menu ứng dụng">
            @foreach ($menu as $item)
                <a href="{{ $item['href'] }}" @class([
                    'flex items-center gap-3 rounded-full px-4 py-2.5 text-sm font-medium transition',
                    'bg-navy-900 text-white' => $item['active'] ?? false,
                    'text-slate-600 hover:bg-slate-100 hover:text-navy-900' => ! ($item['active'] ?? false),
                ]) @if ($item['active'] ?? false) aria-current="page" @endif>
                    <x-ui.icon :name="$item['icon']" class="size-5" /> {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        @isset($sidebarFooter)<div class="border-t border-slate-200 p-4">{{ $sidebarFooter }}</div>@endisset
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <span class="lg:hidden"><x-ui.logo /></span>
            <h1 class="hidden text-lg font-semibold lg:block">{{ $title }}</h1>
            <div class="ml-auto flex items-center gap-2">
                @isset($toolbar){{ $toolbar }}@endisset
                @if ($user)
                    @if ($userUrl)
                        <a href="{{ $userUrl }}" class="flex size-9 items-center justify-center rounded-full bg-navy-900 text-sm font-semibold text-white hover:bg-navy-800" title="Tài khoản: {{ $user }}">{{ mb_substr($user, 0, 1) }}<span class="sr-only">Tài khoản của {{ $user }}</span></a>
                    @else
                        <span class="flex size-9 items-center justify-center rounded-full bg-navy-900 text-sm font-semibold text-white" title="{{ $user }}">{{ mb_substr($user, 0, 1) }}</span>
                    @endif
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 pb-28 pt-5 sm:px-6 lg:pb-10">
            <h1 class="mb-4 text-xl font-semibold lg:hidden">{{ $title }}</h1>
            {{ $slot }}
        </main>
    </div>

    @if ($bottom)
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Điều hướng nhanh">
            @php($plusAt = intdiv(count($bottom), 2))
            <div class="grid items-end" style="grid-template-columns: repeat({{ count($bottom) + ($addUrl ? 1 : 0) }}, minmax(0, 1fr))">
                @foreach ($bottom as $index => $item)
                    @if ($index === $plusAt && $addUrl)
                        <a href="{{ $addUrl }}" class="-mt-5 flex flex-col items-center">
                            <span class="flex size-14 items-center justify-center rounded-2xl bg-navy-900 text-highlight shadow-lg ring-4 ring-white"><x-ui.icon name="plus" class="size-7" /></span>
                            <span class="sr-only">{{ $addLabel }}</span>
                        </a>
                    @endif
                    <a href="{{ $item['href'] }}" @class([
                        'flex flex-col items-center gap-1 py-2 text-[11px] font-medium',
                        'font-semibold text-navy-900' => $item['active'] ?? false,
                        'text-slate-500' => ! ($item['active'] ?? false),
                    ]) @if ($item['active'] ?? false) aria-current="page" @endif>
                        <x-ui.icon :name="$item['icon']" class="size-6" /> {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    <x-ui.toasts />
</body>
</html>
