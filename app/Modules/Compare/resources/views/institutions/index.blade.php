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
            <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($institutions as $institution)
                    <li>
                        <a href="{{ route('compare.institutions.show', $institution->slug) }}" class="group flex h-full items-center gap-4 rounded-card border border-slate-200 bg-white p-4 shadow-card transition hover:-translate-y-0.5 hover:border-primary-300">
                            <span class="flex h-12 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                                @if ($logo = $institution->logoUrl())
                                    <img src="{{ $logo }}" alt="" width="64" height="40" class="max-h-10 max-w-14 object-contain" loading="lazy">
                                @else
                                    <span class="text-xs font-semibold text-slate-500">{{ Str::limit($institution->short_name ?: $institution->name, 8, '') }}</span>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-navy-900 group-hover:text-primary-700">{{ $institution->short_name ?: $institution->name }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $institution->products_count }} sản phẩm</span>
                                @if ($rate = $best->get($institution->id))
                                    <span class="mt-1 block text-sm text-slate-600">12 tháng: <strong class="num text-income">{{ Format::rate($rate) }}</strong></span>
                                @endif
                            </span>
                            <x-ui.icon name="chevron-right" class="size-5 shrink-0 text-slate-400" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
