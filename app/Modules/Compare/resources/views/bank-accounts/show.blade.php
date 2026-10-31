@php
    $logo = $account->institution->logoUrl();
    $partnerLinks = app(\App\Modules\Partner\Actions\PartnerLinks::class)->forProducts($similar->pluck('id')->push($account->id));
@endphp
<x-layouts.public :title="$account->name.' – '.$account->institution->short_name"
    :description="$account->summary ?: 'Phí quản lý, số dư tối thiểu, phí chuyển khoản và tính năng của '.$account->name.' tại '.$account->institution->short_name.'. Dữ liệu có nguồn và ngày xác minh.'"
    :canonical="route('compare.bank-accounts.show', $account->slug)" :seo="$account->seoMeta">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Tài khoản ngân hàng' => route('compare.bank-accounts.index'), $account->name => null]" />

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-card border border-slate-200 bg-white">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $account->institution->short_name }}" width="96" height="56" class="max-h-14 max-w-24 object-contain">
                @else
                    <span class="text-sm font-semibold text-slate-500">{{ $account->institution->short_name }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $account->name }}</h1>
                <p class="mt-1 text-slate-600"><a href="{{ route('compare.institutions.show', $account->institution->slug) }}" class="underline decoration-slate-300 underline-offset-4 hover:text-navy-900">{{ $account->institution->name }}</a></p>
                @if ($features)
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($features as $feature)<x-ui.badge variant="primary">{{ $feature }}</x-ui.badge>@endforeach
                    </div>
                @endif
                @if ($partnerLink = $partnerLinks->get($account->id))
                    <div class="mt-4">
                        <x-ui.button :href="$partnerLink->url()" icon="arrow-top-right-on-square" rel="sponsored nofollow noopener" target="_blank">{{ $partnerLink->cta_label }}</x-ui.button>
                    </div>
                @endif
            </div>
        </div>

        @if ($partnerLinks->isNotEmpty())<x-ui.disclosure class="mt-4" />@endif

        @if ($account->summary)
            <p class="mt-6 max-w-3xl text-slate-700">{{ $account->summary }}</p>
        @endif

        <section class="mt-8" aria-labelledby="bieu-phi">
            <h2 id="bieu-phi" class="text-lg font-semibold">Biểu phí chính</h2>
            <x-ui.card class="mt-3">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4">
                    @foreach ($facts as $label => $value)
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="num mt-0.5 text-sm font-semibold text-navy-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($account->bankAccount->monthly_fee_note)
                    <p class="mt-4 text-sm text-slate-600">{{ $account->bankAccount->monthly_fee_note }}</p>
                @endif
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">"—" là thông số ngân hàng chưa công bố.</p>
            </x-ui.card>
        </section>

        @if ($account->pros || $account->cons)
            <section class="mt-8 grid gap-4 sm:grid-cols-2" aria-label="Ưu điểm và hạn chế">
                @foreach (['Ưu điểm' => [$account->pros, 'check-circle', 'text-green-700'], 'Hạn chế' => [$account->cons, 'minus-circle', 'text-amber-700']] as $heading => [$items, $icon, $color])
                    @if ($items)
                        <x-ui.card>
                            <h2 class="text-base font-semibold">{{ $heading }}</h2>
                            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                @foreach ($items as $item)
                                    <li class="flex gap-2"><x-ui.icon :name="$icon" class="mt-0.5 size-4 shrink-0 {{ $color }}" />{{ $item }}</li>
                                @endforeach
                            </ul>
                        </x-ui.card>
                    @endif
                @endforeach
            </section>
        @endif

        <section class="mt-8" aria-labelledby="nguon">
            <h2 id="nguon" class="text-lg font-semibold">Nguồn dữ liệu</h2>
            <x-ui.data-freshness class="mt-2" :verified-at="$account->verified_at" source="Biểu phí chính thức của ngân hàng"
                :source-url="$account->source_url" :stale="$account->isDueForReview()" />
            @if ($account->source_note)
                <p class="mt-1 text-xs text-slate-500">{{ $account->source_note }}</p>
            @endif
            <p class="mt-3 text-xs text-slate-500">
                Thông tin chỉ để tham khảo. Biểu phí, điều kiện mở tài khoản và ưu đãi do ngân hàng quyết định và có thể thay đổi.
                Đây không phải khuyến nghị tài chính cá nhân.
            </p>
        </section>

        @if ($similar->isNotEmpty())
            <section class="mt-10" aria-labelledby="tai-khoan-tuong-tu">
                <h2 id="tai-khoan-tuong-tu" class="text-lg font-semibold">Tài khoản tương tự</h2>
                <p class="mt-1 text-sm text-slate-500">Có nhiều tính năng chung hoặc phí quản lý gần nhất.</p>
                <div class="mt-4 space-y-4">
                    @foreach ($similar as $other)
                        <x-ui.product-card :id="$other->slug" :name="$other->name" :institution="$other->institution->short_name"
                            :logo="$other->institution->logoUrl()" :detail-url="route('compare.bank-accounts.show', $other->slug)" :comparable="false"
                            :partner-url="$partnerLinks->get($other->id)?->url()" :partner-label="$partnerLinks->get($other->id)?->cta_label ?? ''"
                            :facts="array_slice(\App\Modules\Compare\Support\BankAccountFacts::for($other), 0, 3)" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
