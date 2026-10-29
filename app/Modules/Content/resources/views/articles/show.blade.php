@php
    $updated = $article->updatedAfterPublishing();
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => $article->excerpt,
        'datePublished' => $article->published_at->toAtomString(),
        'dateModified' => $article->lastModified()->toAtomString(),
        'mainEntityOfPage' => $article->url(),
        'author' => ['@type' => 'Person', 'name' => $article->author->name, 'url' => route('content.authors.show', $article->author->slug)],
        'publisher' => ['@type' => 'Organization', 'name' => 'EzFin', 'url' => url('/')],
    ] + ($article->coverUrl() ? ['image' => url($article->coverUrl())] : []);
@endphp
<x-layouts.public :title="$article->title" :description="$article->excerpt" :canonical="$article->url()"
    :seo="$article->seoMeta" :image="$article->coverUrl()" type="article">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <article class="mx-auto max-w-3xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Kiến thức' => route('content.articles.index'), $article->category->name => route('content.categories.show', $article->category->slug), $article->title => null]" />

        <header class="mt-6">
            <a href="{{ route('content.categories.show', $article->category->slug) }}" class="text-xs font-semibold uppercase tracking-wide text-primary-700 hover:underline">{{ $article->category->name }}</a>
            <h1 class="mt-2 text-2xl font-bold leading-tight sm:text-4xl">{{ $article->title }}</h1>
            @if ($article->excerpt)
                <p class="mt-3 text-lg text-slate-600">{{ $article->excerpt }}</p>
            @endif

            <div class="mt-5 flex items-center gap-3 border-y border-slate-200 py-4">
                @include('content::partials.author-avatar', ['author' => $article->author, 'size' => 'size-11 text-base'])
                <div class="min-w-0 text-sm">
                    <p>
                        <a href="{{ route('content.authors.show', $article->author->slug) }}" class="font-semibold text-navy-900 hover:text-primary-700" rel="author">{{ $article->author->name }}</a>
                        @if ($article->author->job_title)<span class="text-slate-500"> · {{ $article->author->job_title }}</span>@endif
                    </p>
                    <p class="mt-0.5 flex flex-wrap gap-x-2 text-slate-500">
                        <span>Xuất bản <time datetime="{{ $article->published_at->toDateString() }}">{{ \App\Support\Format::date($article->published_at) }}</time></span>
                        @if ($updated)<span>· Cập nhật <time datetime="{{ $updated->toDateString() }}">{{ \App\Support\Format::date($updated) }}</time></span>@endif
                        <span>· {{ $article->readingMinutes() }} phút đọc</span>
                    </p>
                </div>
            </div>
        </header>

        @if ($article->coverUrl())
            <img src="{{ $article->coverUrl() }}" alt="" class="mt-6 aspect-[16/9] w-full rounded-card object-cover">
        @endif

        {{-- Nội dung đã lọc XSS khi lưu (App\Modules\Content\Support\ArticleHtml). --}}
        <div class="article-body mt-8">{!! $article->body !!}</div>

        @if ($article->references)
            <section class="mt-10" aria-labelledby="nguon-tham-khao">
                <h2 id="nguon-tham-khao" class="text-lg font-semibold">Nguồn tham khảo</h2>
                <ol class="mt-3 list-decimal space-y-1.5 pl-6 text-sm text-slate-600">
                    @foreach ($article->references as $reference)
                        <li>
                            @if (! empty($reference['url']))
                                <a href="{{ $reference['url'] }}" class="text-primary-700 underline decoration-primary-200 underline-offset-2 hover:decoration-primary-600" rel="nofollow noopener" target="_blank">{{ $reference['title'] }}</a>
                            @else
                                {{ $reference['title'] }}
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <x-ui.alert class="mt-10" variant="info">
            Bài viết chỉ cung cấp thông tin chung để tham khảo, không phải khuyến nghị tài chính hay đầu tư cho trường hợp cụ thể của bạn.
            Lãi suất, phí và điều kiện sản phẩm do tổ chức tài chính quyết định và có thể thay đổi.
        </x-ui.alert>

        <section class="mt-8 flex gap-4 rounded-card border border-slate-200 bg-white p-4 sm:p-5" aria-label="Về tác giả">
            @include('content::partials.author-avatar', ['author' => $article->author, 'size' => 'size-14 text-lg'])
            <div class="min-w-0">
                <p class="text-xs text-slate-500">Tác giả</p>
                <a href="{{ route('content.authors.show', $article->author->slug) }}" class="font-semibold text-navy-900 hover:text-primary-700">{{ $article->author->name }}</a>
                @if ($article->author->job_title)<p class="text-sm text-slate-600">{{ $article->author->job_title }}</p>@endif
                @if ($article->author->bio)<p class="mt-2 line-clamp-4 text-sm text-slate-600">{{ $article->author->bio }}</p>@endif
            </div>
        </section>
    </article>

    @if ($related->isNotEmpty())
        <section class="mx-auto mt-6 max-w-7xl px-4 sm:px-6" aria-labelledby="bai-lien-quan">
            <h2 id="bai-lien-quan" class="text-lg font-semibold">Bài cùng chuyên mục</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-ui.article-card :title="$item->title" :url="$item->url()" :excerpt="$item->excerpt" :author="$item->author->name"
                        :date="$item->published_at" :minutes="$item->readingMinutes()" :cover="$item->coverUrl()" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.public>
