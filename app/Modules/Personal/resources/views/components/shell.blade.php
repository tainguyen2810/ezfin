{{-- Khung mọi màn hình của ứng dụng quản lý tiền: menu theo các màn hình đã có, người dùng, đăng xuất. --}}
@props(['title'])
@php
    $menu = \App\Modules\Personal\Support\AppMenu::class;
    [$addUrl, $addLabel] = $menu::add();
@endphp
<x-layouts.app :title="$title" :menu="$menu::items()" :bottom="$menu::bottom()" :add-url="$addUrl" :add-label="$addLabel" :user="auth()->user()->name">
    <x-slot:toolbar>
        @isset($actions){{ $actions }}@endisset
        <form method="POST" action="{{ route('personal.logout') }}" class="lg:hidden">
            @csrf
            <button type="submit" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="arrow-right-start-on-rectangle" class="size-6" /><span class="sr-only">Đăng xuất</span></button>
        </form>
    </x-slot:toolbar>
    <x-slot:sidebarFooter>
        <p class="truncate text-sm font-semibold text-navy-900">{{ auth()->user()->name }}</p>
        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
        <form method="POST" action="{{ route('personal.logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-navy-900"><x-ui.icon name="arrow-right-start-on-rectangle" class="size-5" />Đăng xuất</button>
        </form>
    </x-slot:sidebarFooter>

    {{ $slot }}
</x-layouts.app>
