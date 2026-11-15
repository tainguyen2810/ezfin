<x-layouts.public title="Ngân hàng và công ty tài chính"
    description="Danh sách ngân hàng, công ty tài chính EzFin đang so sánh: lãi suất tiết kiệm, thẻ tín dụng, tài khoản thanh toán. Dữ liệu có nguồn và ngày xác minh."
    :canonical="route('compare.institutions.index')">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Ngân hàng' => null]" />
        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold sm:text-3xl">Ngân hàng và công ty tài chính</h1>
                <p class="mt-1 max-w-3xl text-slate-600">Xem lãi suất, thẻ và tài khoản của từng tổ chức EzFin đang so sánh.</p>
            </div>
            <x-ui.button :href="route('compare.savings.table')" variant="secondary" icon="table-cells">Bảng lãi suất</x-ui.button>
        </div>

        @if ($institutions->isEmpty())
            <x-ui.card class="mt-6"><x-ui.empty-state icon="building-library" title="Chưa có dữ liệu" description="Danh sách đang được cập nhật." /></x-ui.card>
        @else
            @php
                $types = [
                    'savings' => 'Tiết kiệm', 'credit_card' => 'Thẻ tín dụng', 'loan' => 'Khoản vay', 'bank_account' => 'Tài khoản',
                ];
            @endphp
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($institutions as $institution)
                    @php $name = $institution->short_name ?: $institution->name; $rate = $best->get($institution->id); @endphp
                    <li>
                        <a href="{{ route('compare.institutions.show', $institution->slug) }}" class="group flex h-full flex-col rounded-card border border-slate-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-lg">
                            <div class="p-4 pb-0">
                                <div class="flex h-24 items-center justify-center rounded-xl bg-gradient-to-br from-slate-50 to-primary-50">
                                    @if ($logo = $institution->logoUrl())
                                        <img src="{{ $logo }}" alt="{{ $name }}" width="144" height="56" class="max-h-14 max-w-36 object-contain transition group-hover:scale-105" loading="lazy">
                                    @else
                                        <span class="text-xl font-bold text-navy-800">{{ $name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h2 class="font-semibold text-navy-900 group-hover:text-primary-700">{{ $name }}</h2>
                                        <p class="text-xs text-slate-500">{{ $institution->type?->getLabel() }} · {{ $institution->products_count }} sản phẩm</p>
                                    </div>
                                    @if ($rate)
                                        <div class="shrink-0 rounded-lg bg-income-soft px-2.5 py-1 text-right">
                                            <p class="text-[11px] text-slate-600">Tiết kiệm 12 tháng</p>
                                            <p class="num text-sm font-bold text-income">{{ Format::rate($rate) }}</p>
                                        </div>
                                    @endif
                                </div>
                                <dl class="mt-3 grid grid-cols-4 gap-2 border-t border-slate-100 pt-3 text-center">
                                    @foreach ($types as $key => $label)
                                        <div>
                                            <dd class="num text-base font-semibold text-navy-900">{{ $institution->{$key.'_count'} ?: '—' }}</dd>
                                            <dt class="text-[11px] leading-tight text-slate-500">{{ $label }}</dt>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                            <span class="flex items-center gap-1 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-primary-700">
                                Xem lãi suất và sản phẩm <x-ui.icon name="chevron-right" class="size-4" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
