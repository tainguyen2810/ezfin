@php($isOnline = $channel === \App\Modules\Compare\Enums\SavingsChannel::Online)
<x-layouts.public title="Bảng lãi suất tiết kiệm các ngân hàng"
    description="Bảng lãi suất tiết kiệm {{ mb_strtolower($channel->getLabel()) }} của các ngân hàng theo từng kỳ hạn từ 1 đến 36 tháng, đánh dấu mức cao nhất mỗi kỳ hạn. Dữ liệu có nguồn và ngày xác minh."
    :canonical="$isOnline ? route('compare.savings.table') : null" :noindex="! $isOnline">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Lãi suất tiết kiệm' => route('compare.savings.index'), 'Bảng lãi suất' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Bảng lãi suất tiết kiệm các ngân hàng</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Mức lãi cao nhất mỗi ngân hàng đang áp dụng cho từng kỳ hạn, ở mức tiền gửi thấp nhất, lĩnh lãi cuối kỳ hoặc hình thức có lãi cao nhất. Ô được tô là mức cao nhất của kỳ hạn đó.</p>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <x-ui.tabs class="w-full max-w-xs" label="Kênh gửi" :items="$channels->map(fn ($label, $slug) => [
                'label' => $label, 'href' => route('compare.savings.table', $slug === 'online' ? [] : ['kenh' => $slug]), 'active' => $slug === $channel->slug(),
            ])->values()->all()" />
            <a href="{{ route('compare.savings.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:underline">Tính tiền lãi theo số tiền của bạn <x-ui.icon name="chevron-right" class="size-4" /></a>
        </div>

        @if ($rows->isEmpty())
            <x-ui.card class="mt-6"><x-ui.empty-state icon="banknotes" title="Chưa có lãi suất" description="Dữ liệu lãi suất đang được cập nhật và xác minh." /></x-ui.card>
        @else
            <x-ui.table class="mt-4">
                <caption class="sr-only">Lãi suất tiết kiệm {{ mb_strtolower($channel->getLabel()) }} theo kỳ hạn, %/năm</caption>
                <thead>
                    <tr>
                        <th scope="col" class="sticky left-0 bg-slate-50">Ngân hàng</th>
                        @foreach ($terms as $term)<th scope="col" class="text-right whitespace-nowrap">{{ $term }} th</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('compare.institutions.show', $row['institution']->slug) }}" class="flex items-center gap-2 font-semibold text-navy-900 hover:text-primary-700">
                                    @if ($logo = $row['institution']->logoUrl())<img src="{{ $logo }}" alt="" width="40" height="25" class="h-6 w-10 rounded object-contain" loading="lazy">@endif
                                    {{ $row['institution']->short_name ?: $row['institution']->name }}
                                </a>
                            </td>
                            @foreach ($terms as $term)
                                @php($rate = $row['rates'][$term])
                                <td @class(['num text-right whitespace-nowrap', 'font-bold text-navy-900' => $rate !== null && $rate === $best[$term]])>
                                    @if ($rate === null)
                                        <span class="text-slate-400">—</span>
                                    @elseif ($rate === $best[$term])
                                        <span class="rounded-full bg-highlight px-2 py-0.5">{{ Format::percent($rate) }}</span>
                                    @else
                                        {{ Format::percent($rate) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            <p class="mt-3 text-xs text-slate-500">Đơn vị %/năm. "—": ngân hàng chưa công bố kỳ hạn này ở kênh {{ mb_strtolower($channel->getLabel()) }}. Lãi suất do ngân hàng quyết định và có thể thay đổi; xem nguồn và ngày xác minh ở trang từng ngân hàng.</p>
        @endif
    </div>
</x-layouts.public>
