@php
    use App\Modules\Compare\Support\SavingsFilters;

    $sections = collect([
        ['compare.savings.index', 'compare', 'banknotes', 'Lãi suất tiết kiệm', 'So sánh theo số tiền và kỳ hạn'],
        ['compare.credit-cards.index', 'compare', 'credit-card', 'Thẻ tín dụng', 'Phí, hoàn tiền, điều kiện mở thẻ'],
        ['compare.bank-accounts.index', 'compare', 'building-library', 'Tài khoản ngân hàng', 'Phí quản lý, chuyển khoản, ATM'],
        ['planner.tools.index', 'planner', 'calculator', 'Công cụ tính', 'Lãi kép, khoản vay, trả góp'],
        ['content.articles.index', 'content', 'book-open', 'Kiến thức', 'Giải thích dễ hiểu, có nguồn'],
    ])->filter(fn ($item) => Route::has($item[0]) && \Laravel\Pennant\Feature::active($item[1]));
    $terms = collect(SavingsFilters::TERMS)->mapWithKeys(fn ($term) => [$term => $term.' tháng'])->all();
@endphp
<x-layouts.public :canonical="route('home')"
    description="So sánh lãi suất tiết kiệm, thẻ tín dụng, tài khoản ngân hàng và dùng công cụ tính lãi, khoản vay miễn phí. Dữ liệu có nguồn và ngày xác minh, xếp hạng không theo hoa hồng.">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[3fr_2fr] lg:items-center lg:py-16">
            <div>
                <h1 class="text-3xl font-bold leading-tight sm:text-5xl">Tài chính thông minh cho <span class="text-primary-600">cuộc sống tốt hơn</span></h1>
                <p class="mt-4 max-w-xl text-lg text-slate-600">So sánh sản phẩm tài chính và tính toán trước khi quyết định. Dữ liệu lấy từ nguồn chính thức của ngân hàng, ghi rõ ngày xác minh.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($compare)
                        <x-ui.button :href="route('compare.savings.index')" size="lg">So sánh lãi suất</x-ui.button>
                    @endif
                    @if ($tools)
                        <x-ui.button :href="route('planner.tools.index')" variant="secondary" size="lg">Công cụ tính</x-ui.button>
                    @endif
                </div>
            </div>

            @if ($compare)
                <x-ui.card class="lg:ml-auto lg:w-full lg:max-w-md">
                    <h2 class="text-lg font-semibold">Gửi tiết kiệm ở đâu lãi cao?</h2>
                    <p class="mt-1 text-sm text-slate-600">Nhập số tiền và kỳ hạn, EzFin xếp các ngân hàng theo tiền lãi bạn nhận được.</p>
                    <form method="GET" action="{{ route('compare.savings.index') }}" class="mt-4 space-y-4">
                        <x-ui.money-input name="so_tien" label="Số tiền gửi" :value="$savingsFilters->amount" required />
                        <x-ui.select name="ky_han" label="Kỳ hạn" :options="$terms" :value="$savingsFilters->term" />
                        <x-ui.button type="submit" class="w-full">Xem lãi suất</x-ui.button>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-10 sm:px-6">
        @if ($sections->isNotEmpty())
            <nav aria-label="Khám phá">
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($sections as [$route, , $icon, $label, $hint])
                        <li>
                            <a href="{{ route($route) }}" class="group flex h-full flex-col gap-3 rounded-card border border-slate-200 bg-white p-4 shadow-card transition hover:border-primary-300">
                                <span class="flex size-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-ui.icon :name="$icon" class="size-5" /></span>
                                <span>
                                    <span class="block font-semibold text-navy-900 group-hover:text-primary-700">{{ $label }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $hint }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($savings->isNotEmpty())
            <section aria-labelledby="lai-suat-cao">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 id="lai-suat-cao" class="text-xl font-bold">Lãi suất tiết kiệm cao nhất kỳ hạn {{ $savingsFilters->term }} tháng</h2>
                        <p class="mt-1 text-sm text-slate-600">Khoản gửi {{ Format::money($savingsFilters->amount) }}, lĩnh lãi cuối kỳ, xếp theo tiền lãi nhận được.</p>
                    </div>
                    <a href="{{ route('compare.savings.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:underline">Đổi số tiền, kỳ hạn<x-ui.icon name="chevron-right" class="size-4" /></a>
                </div>
                <ol class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-card border border-slate-200 bg-white shadow-card">
                    @foreach ($savings as $result)
                        @php $rate = $result['rate']; $product = $rate->product; $logo = $product->institution->logoUrl(); @endphp
                        <li class="flex items-center gap-3 px-4 py-3 sm:gap-4 sm:px-5">
                            <span class="num w-5 shrink-0 text-sm font-semibold text-slate-400">{{ $loop->iteration }}</span>
                            <span class="flex h-10 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                @if ($logo)<img src="{{ $logo }}" alt="" class="max-h-7 max-w-12 object-contain">@else<span class="text-xs font-semibold text-slate-500">{{ $product->institution->short_name }}</span>@endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-navy-900">{{ $product->institution->short_name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $product->name }} · {{ $rate->channel->getLabel() }} · hiệu lực từ {{ Format::date($rate->effective_from) }}</span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="num block font-bold text-navy-900">{{ Format::rate($rate->rate) }}</span>
                                <span class="num block text-xs text-income">+{{ Format::money($result['interest']) }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-2 text-xs text-slate-500">Nguồn và ngày xác minh của từng ngân hàng có ở trang so sánh lãi suất. Lãi suất do ngân hàng quyết định và có thể thay đổi.</p>
            </section>
        @endif

        @if ($tools)
            <section aria-labelledby="cong-cu">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <h2 id="cong-cu" class="text-xl font-bold">Tính trước khi quyết định</h2>
                    <a href="{{ route('planner.tools.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:underline">Tất cả công cụ<x-ui.icon name="chevron-right" class="size-4" /></a>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tools as $tool)
                        <a href="{{ route('planner.tools.show', $tool->slug()) }}" class="group flex gap-3 rounded-card border border-slate-200 bg-white p-4 shadow-card transition hover:border-primary-300">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-ui.icon :name="$tool->icon()" class="size-5" /></span>
                            <span>
                                <span class="block font-semibold text-navy-900 group-hover:text-primary-700">{{ $tool->title() }}</span>
                                <span class="mt-0.5 block text-sm text-slate-600">{{ $tool->summary() }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($articles->isNotEmpty())
            <section aria-labelledby="kien-thuc">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <h2 id="kien-thuc" class="text-xl font-bold">Kiến thức mới</h2>
                    <a href="{{ route('content.articles.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:underline">Xem tất cả<x-ui.icon name="chevron-right" class="size-4" /></a>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $article)
                        <x-ui.article-card :title="$article->title" :url="$article->url()" :excerpt="$article->excerpt" :category="$article->category->name"
                            :author="$article->author->name" :date="$article->published_at" :minutes="$article->readingMinutes()" :cover="$article->coverUrl()" />
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="cach-lam-viec" class="rounded-card bg-navy-900 px-5 py-8 text-navy-100 sm:px-8">
            <h2 id="cach-lam-viec" class="text-xl font-bold text-white">EzFin làm việc thế nào</h2>
            <ul class="mt-5 grid gap-5 md:grid-cols-3">
                <li>
                    <p class="flex items-center gap-2 font-semibold text-white"><x-ui.icon name="check-circle" class="size-5 text-green-400" />Có nguồn, có ngày xác minh</p>
                    <p class="mt-1 text-sm">Mỗi sản phẩm ghi rõ nguồn chính thức và ngày kiểm tra gần nhất. Thiếu nguồn thì không đăng.</p>
                </li>
                <li>
                    <p class="flex items-center gap-2 font-semibold text-white"><x-ui.icon name="scale" class="size-5 text-green-400" />Xếp hạng không theo hoa hồng</p>
                    <p class="mt-1 text-sm">Thứ tự dựa trên tiền lãi, phí và tiêu chí bạn chọn. Đối tác không trả tiền để được xếp cao hơn.</p>
                </li>
                <li>
                    <p class="flex items-center gap-2 font-semibold text-white"><x-ui.icon name="information-circle" class="size-5 text-green-400" />Minh bạch cách kiếm tiền</p>
                    <p class="mt-1 text-sm">EzFin có thể nhận hoa hồng khi bạn mở sản phẩm qua liên kết đối tác. Bạn không phải trả thêm phí.</p>
                </li>
            </ul>
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold">
                <a href="{{ route('legal.methodology') }}" class="text-white underline decoration-navy-400 underline-offset-4 hover:decoration-white">Phương pháp đánh giá</a>
                <a href="{{ route('legal.disclosure') }}" class="text-white underline decoration-navy-400 underline-offset-4 hover:decoration-white">Công bố liên kết</a>
            </div>
        </section>
    </div>
</x-layouts.public>
