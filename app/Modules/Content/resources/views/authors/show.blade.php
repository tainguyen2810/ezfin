<x-layouts.public :title="$author->name.' – Tác giả'"
    :description="$author->bio ? \Illuminate\Support\Str::limit($author->bio, 155) : 'Các bài viết của '.$author->name.' trên EzFin.'"
    :canonical="route('content.authors.show', $author->slug)">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $author->name,
                'jobTitle' => $author->job_title,
                'description' => $author->bio,
                'image' => $author->avatarUrl() ? url($author->avatarUrl()) : null,
                'sameAs' => $author->links ?: null,
            ]),
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Kiến thức' => route('content.articles.index'), $author->name => null]" />

        <header class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start">
            @include('content::partials.author-avatar', ['author' => $author, 'size' => 'size-20 text-2xl'])
            <div class="min-w-0 max-w-3xl">
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $author->name }}</h1>
                @if ($author->job_title)<p class="mt-1 text-slate-600">{{ $author->job_title }}</p>@endif
                @if ($author->expertise)
                    <p class="mt-2 text-sm text-slate-600"><span class="font-medium text-navy-900">Chuyên môn:</span> {{ $author->expertise }}</p>
                @endif
                @if ($author->bio)
                    <div class="mt-3 space-y-2 text-slate-700">
                        @foreach (preg_split('/\R{2,}/u', trim($author->bio)) as $paragraph)
                            <p>{!! nl2br(e($paragraph)) !!}</p>
                        @endforeach
                    </div>
                @endif
                @if ($author->links)
                    <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        @foreach ($author->links as $link)
                            <li><a href="{{ $link }}" class="inline-flex items-center gap-1 text-primary-700 hover:underline" rel="me noopener" target="_blank">{{ parse_url($link, PHP_URL_HOST) }}<x-ui.icon name="arrow-top-right-on-square" class="size-3.5" /></a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </header>

        <section class="mt-10" aria-labelledby="bai-viet">
            <h2 id="bai-viet" class="text-lg font-semibold">Bài viết</h2>
            <div class="mt-4">
                @if ($articles->isEmpty())
                    <x-ui.card><x-ui.empty-state icon="book-open" title="Chưa có bài viết được xuất bản" /></x-ui.card>
                @else
                    @include('content::partials.article-grid', ['articles' => $articles])
                @endif
            </div>
        </section>
    </div>
</x-layouts.public>
