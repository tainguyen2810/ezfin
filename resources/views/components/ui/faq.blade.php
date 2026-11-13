{{-- Câu hỏi thường gặp: items = [['question' => '...', 'answer' => '...'], ...] (văn bản thuần, được escape).
     Mở/đóng bằng <details>, chạy khi tắt JS. Kèm dữ liệu FAQPage (schema.org) trừ khi :schema="false". --}}
@props(['items', 'title' => 'Câu hỏi thường gặp', 'schema' => true])
@php
    $items = collect($items)->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))->values();
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $items->map(fn ($item) => [
            '@type' => 'Question',
            'name' => (string) $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) $item['answer']],
        ])->all(),
    ];
@endphp
@if ($items->isNotEmpty())
    <section {{ $attributes->merge(['class' => '']) }} aria-labelledby="faq-heading">
        @if ($title)<h2 id="faq-heading" class="text-xl font-bold text-navy-900">{{ $title }}</h2>@endif
        <div class="mt-3 divide-y divide-slate-200 rounded-card border border-slate-200 bg-white">
            @foreach ($items as $item)
                <details class="group px-4 py-3 sm:px-5" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-3 font-semibold text-navy-900 [&::-webkit-details-marker]:hidden">
                        <span>{{ $item['question'] }}</span>
                        <x-ui.icon name="chevron-down" class="mt-0.5 size-5 shrink-0 text-slate-400 transition group-open:rotate-180" />
                    </summary>
                    <p class="mt-2 whitespace-pre-line text-slate-600">{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </section>
    @if ($schema)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
@endif
