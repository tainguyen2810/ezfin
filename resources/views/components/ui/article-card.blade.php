{{-- Thẻ bài viết trong danh sách kiến thức. date: ngày xuất bản (Carbon), minutes: phút đọc. --}}
@props(['title', 'url', 'excerpt' => null, 'category' => null, 'author' => null, 'date' => null, 'minutes' => null, 'cover' => null])
<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-card border border-slate-200 bg-white shadow-card transition hover:border-primary-200']) }}>
    @if ($cover)
        <img src="{{ $cover }}" alt="" width="1200" height="675" loading="lazy" class="aspect-[16/9] w-full object-cover">
    @endif
    <div class="flex flex-1 flex-col p-4 sm:p-5">
        @if ($category)<p class="text-xs font-semibold uppercase tracking-wide text-primary-700">{{ $category }}</p>@endif
        <h3 class="mt-1 text-base font-semibold leading-snug sm:text-lg">
            <a href="{{ $url }}" class="after:absolute after:inset-0 group-hover:text-primary-700">{{ $title }}</a>
        </h3>
        @if ($excerpt)<p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $excerpt }}</p>@endif
        @if ($author || $date || $minutes)
            <p class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 pt-4 text-xs text-slate-500">
                @if ($author)<span>{{ $author }}</span>@endif
                @if ($date)<span aria-hidden="true">·</span><time datetime="{{ $date->toDateString() }}">{{ \App\Support\Format::date($date) }}</time>@endif
                @if ($minutes)<span aria-hidden="true">·</span><span>{{ $minutes }} phút đọc</span>@endif
            </p>
        @endif
    </div>
</article>
