@php
    $title = $category?->name ?? 'Kiến thức tài chính';
    $page = $articles->currentPage();
@endphp
<x-layouts.public :title="$title.($page > 1 ? ' – Trang '.$page : '')"
    :description="$category?->description ?: 'Bài viết giải thích lãi suất, thẻ tín dụng, tài khoản ngân hàng, vay và tiết kiệm bằng tiếng Việt dễ hiểu, có nguồn tham khảo và ngày cập nhật.'"
    :canonical="$category ? route('content.categories.show', $category->slug) : route('content.articles.index')"
    :noindex="$articles->isEmpty()">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="$category
            ? ['Trang chủ' => route('home'), 'Kiến thức' => route('content.articles.index'), $category->name => null]
            : ['Trang chủ' => route('home'), 'Kiến thức' => null]" />

        <h1 class="mt-4 text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
        <p class="mt-2 max-w-3xl text-slate-600">
            {{ $category?->description ?: 'Giải thích các khái niệm tài chính cá nhân bằng ngôn ngữ dễ hiểu. Mỗi bài ghi rõ tác giả, nguồn tham khảo và ngày cập nhật.' }}
        </p>

        @if ($categories->isNotEmpty())
            <nav class="-mx-4 mt-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Chuyên mục">
                <ul class="flex gap-2 whitespace-nowrap sm:flex-wrap">
                    <li>
                        <a href="{{ route('content.articles.index') }}" @class([
                            'inline-flex rounded-full border px-3 py-1.5 text-sm font-medium',
                            'border-primary-600 bg-primary-600 text-white' => ! $category,
                            'border-slate-200 bg-white text-slate-700 hover:border-primary-200' => $category,
                        ]) @if (! $category) aria-current="page" @endif>Tất cả</a>
                    </li>
                    @foreach ($categories as $item)
                        @php $active = $category?->is($item); @endphp
                        <li>
                            <a href="{{ route('content.categories.show', $item->slug) }}" @class([
                                'inline-flex rounded-full border px-3 py-1.5 text-sm font-medium',
                                'border-primary-600 bg-primary-600 text-white' => $active,
                                'border-slate-200 bg-white text-slate-700 hover:border-primary-200' => ! $active,
                            ]) @if ($active) aria-current="page" @endif>{{ $item->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="mt-6">
            @if ($articles->isEmpty())
                <x-ui.card>
                    <x-ui.empty-state icon="book-open" title="Chưa có bài viết"
                        description="{{ $category ? 'Chuyên mục này chưa có bài viết được xuất bản.' : 'Các bài viết đang được biên soạn và kiểm duyệt.' }}" />
                </x-ui.card>
            @else
                @include('content::partials.article-grid', ['articles' => $articles, 'showCategory' => ! $category])
            @endif
        </div>
    </div>
</x-layouts.public>
