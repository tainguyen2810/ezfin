{{-- Khung trang thông tin/pháp lý (<x-core::legal-page>): title, description, updated (dd/mm/yyyy), nội dung trong slot. --}}
@props(['title', 'description' => null, 'updated' => null])
<x-layouts.public :title="$title" :description="$description">
    <article class="mx-auto max-w-3xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), $title => null]" />
        <h1 class="mt-4 text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
        @if ($updated ?? null)<p class="mt-2 text-sm text-slate-500">Cập nhật lần cuối: {{ $updated }}</p>@endif
        <div class="article-body mt-6">{{ $slot }}</div>
    </article>
</x-layouts.public>
