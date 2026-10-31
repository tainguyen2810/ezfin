{{-- Khung trang đăng nhập, đăng ký, quên mật khẩu của ứng dụng (app.ezfin.vn, noindex). --}}
@props(['title', 'subtitle' => null])
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | EzFin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-white via-slate-50 to-primary-50">
    <x-ui.demo-notice />
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mx-auto"><x-ui.logo /><span class="sr-only">Về trang chủ EzFin</span></a>
        <div class="mt-6 rounded-card border border-slate-200 bg-white p-6 shadow-card sm:p-8">
            <h1 class="text-2xl font-bold">{{ $title }}</h1>
            @if ($subtitle)<p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>@endif
            @if (session('status'))
                <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert>
            @endif
            <div class="mt-6">{{ $slot }}</div>
        </div>
        @isset($footer)<p class="mt-6 text-center text-sm text-slate-600">{{ $footer }}</p>@endisset
    </main>
</body>
</html>
