@php
    use App\Modules\Compare\Support\CardQuizAnswers;

    $partnerLinks = $result ? app(\App\Modules\Partner\Actions\PartnerLinks::class)->forProducts($result['cards']->pluck('card.id')) : collect();
    $chip = 'block rounded-control border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:text-primary-800 peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600';
@endphp
<x-layouts.public title="Gợi ý thẻ tín dụng phù hợp với bạn"
    description="Trả lời 5 câu hỏi về thu nhập, chi tiêu và cách trả nợ để xem thẻ tín dụng phù hợp, kèm lý do và ước tính hoàn tiền trừ phí. Không cần đăng ký, không lưu câu trả lời."
    :canonical="route('compare.credit-cards.quiz')" :noindex="$submitted">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Thẻ tín dụng' => route('compare.credit-cards.index'), 'Gợi ý thẻ' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Thẻ tín dụng nào hợp với bạn?</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Trả lời 5 câu hỏi, EzFin xếp các thẻ bạn đủ điều kiện theo cách bạn chi tiêu và trả nợ. Câu trả lời chỉ dùng để tính trên trang này, không được lưu lại.</p>

        <x-ui.card class="mt-6">
            <form method="GET" action="{{ route('compare.credit-cards.quiz') }}" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.money-input name="thu_nhap" label="1. Thu nhập hằng tháng của bạn" :value="$answers->income" required />
                    <x-ui.money-input name="chi_tieu" label="2. Bạn định chi tiêu bằng thẻ bao nhiêu mỗi tháng?" :value="$answers->spend" required />
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-navy-900">3. Bạn muốn thẻ có lợi cho việc gì? <span class="font-normal text-slate-500">(chọn một hoặc nhiều)</span></legend>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($priorities as $slug => $label)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="uu_tien[]" value="{{ $slug }}" class="peer sr-only" @checked(in_array($slug, $answers->prioritySlugs(), true))>
                                <span class="{{ $chip }}">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-medium text-navy-900">4. Bạn trả nợ thẻ thế nào? <span class="text-red-600" aria-hidden="true">*</span></legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach (CardQuizAnswers::REPAYMENT as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="tra_no" value="{{ $value }}" class="peer sr-only" @checked($answers->repayment === $value) required>
                                <span class="{{ $chip }}">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-sm font-medium text-navy-900">5. Bạn có hay chi tiêu ở nước ngoài hoặc mua hàng bằng ngoại tệ?</legend>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:max-w-md">
                        @foreach (['co' => 'Có', 'khong' => 'Không'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="nuoc_ngoai" value="{{ $value }}" class="peer sr-only" @checked(($answers->abroad ? 'co' : ($submitted ? 'khong' : null)) === $value)>
                                <span class="{{ $chip }} text-center">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button type="submit" icon="sparkles">Xem thẻ phù hợp</x-ui.button>
                    @if ($submitted)
                        <a href="{{ route('compare.credit-cards.quiz') }}" class="text-sm font-medium text-slate-600 underline underline-offset-4">Làm lại</a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        @if ($submitted && ! $result)
            <x-ui.alert variant="warning" class="mt-6">Cần nhập thu nhập, mức chi tiêu và chọn cách trả nợ để xem gợi ý.</x-ui.alert>
        @endif

        @if ($result)
            <section class="mt-8" aria-labelledby="ket-qua">
                <h2 id="ket-qua" class="text-xl font-bold">Thẻ phù hợp nhất với bạn</h2>
                <p class="mt-1 text-sm text-slate-600" role="status">
                    @if ($result['eligible'] > 0)
                        Bạn đủ điều kiện thu nhập cho {{ $result['eligible'] }} thẻ{{ $result['excluded'] > 0 ? ', '.$result['excluded'].' thẻ khác yêu cầu thu nhập cao hơn' : '' }}.
                        {{ $answers->carriesBalance() ? 'Vì có tháng bạn chỉ trả một phần, thẻ lãi suất thấp được xếp trước.' : 'Xếp theo mức hợp với ưu tiên của bạn, rồi hoàn tiền ước tính trừ phí thường niên.' }}
                    @endif
                </p>

                @if ($partnerLinks->isNotEmpty())<x-ui.disclosure class="mt-4" />@endif

                <ol class="mt-4 space-y-4">
                    @forelse ($result['cards'] as $i => $item)
                        @php $card = $item['card']; @endphp
                        <li>
                            <x-ui.product-card :id="$card->slug" :name="($i + 1).'. '.$card->name" :institution="$card->institution->short_name"
                                :logo="$card->institution->logoUrl()" :image="$card->imageUrl()" :comparable="false"
                                :highlights="collect($item['matches'])->map->getLabel()->all()"
                                :detail-url="route('compare.credit-cards.show', $card->slug)" :partner-url="$partnerLinks->get($card->id)?->url()" :partner-label="$partnerLinks->get($card->id)?->cta_label ?? ''"
                                :facts="[
                                    'Hoàn tiền ước tính' => $item['cashback'] === null ? '—' : Format::money($item['cashback']).'/năm',
                                    'Phí thường niên' => $card->creditCard->annual_fee === null ? '—' : ((float) $card->creditCard->annual_fee === 0.0 ? 'Miễn phí' : Format::money($card->creditCard->annual_fee)),
                                    'Lãi suất' => Format::rate($card->creditCard->interest_rate),
                                    'Phí ngoại tệ' => Format::percent($card->creditCard->foreign_transaction_fee),
                                ]">
                                <x-slot:footer>
                                    <p class="text-sm font-semibold text-navy-900">Vì sao thẻ này hợp với bạn</p>
                                    <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                                        @foreach ($item['reasons'] as $reason)
                                            <li class="flex gap-2"><x-ui.icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-primary-600" />{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                    <x-ui.data-freshness class="mt-3" :verified-at="$card->verified_at" source="Trang chính thức của ngân hàng"
                                        :source-url="$card->source_url" :stale="$card->isDueForReview()" />
                                </x-slot:footer>
                            </x-ui.product-card>
                        </li>
                    @empty
                        <li>
                            <x-ui.card>
                                <x-ui.empty-state icon="credit-card" title="Chưa có thẻ phù hợp với thu nhập này"
                                    description="Các thẻ EzFin đang có đều yêu cầu thu nhập cao hơn. Bạn có thể xem thẻ ghi nợ hoặc tài khoản thanh toán miễn phí.">
                                    <x-ui.button :href="route('compare.credit-cards.index')" variant="outline">Xem mọi thẻ tín dụng</x-ui.button>
                                </x-ui.empty-state>
                            </x-ui.card>
                        </li>
                    @endforelse
                </ol>

                <p class="mt-4 text-xs text-slate-500">
                    Hoàn tiền ước tính = mức chi tiêu × tỷ lệ hoàn tiền tối đa ngân hàng công bố, giới hạn bởi trần mỗi tháng, nhân 12. Nhiều thẻ chỉ hoàn mức tối đa cho một số nhóm chi tiêu nên số thực tế có thể thấp hơn.
                    Thứ tự không phụ thuộc quan hệ đối tác; xem <a href="{{ route('legal.methodology') }}" class="underline">Phương pháp đánh giá</a>. Ngân hàng quyết định việc cấp thẻ và hạn mức. Đây không phải khuyến nghị tài chính cá nhân.
                </p>
            </section>
        @endif
    </div>
</x-layouts.public>
