{{-- $articles: paginator bài đã xuất bản (đã nạp category, author). $showCategory: hiện tên chuyên mục trên thẻ. --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($articles as $item)
        <x-ui.article-card :title="$item->title" :url="$item->url()" :excerpt="$item->excerpt"
            :category="($showCategory ?? true) ? $item->category->name : null" :author="$item->author->name"
            :date="$item->published_at" :minutes="$item->readingMinutes()" :cover="$item->coverUrl()" />
    @endforeach
</div>
@if ($articles->hasPages())
    <div class="mt-8">{{ $articles->links('pagination::tailwind') }}</div>
@endif
