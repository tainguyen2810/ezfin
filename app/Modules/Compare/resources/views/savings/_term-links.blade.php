{{-- Liên kết nội bộ tới trang đích "Lãi suất X tháng cao nhất" (chỉ kỳ hạn đang có dữ liệu). --}}
@php($landingTerms = app(\App\Modules\Compare\Actions\BestSavingsRates::class)->availableTerms())
@if ($landingTerms)
    <nav class="mt-8" aria-label="Lãi suất cao nhất theo kỳ hạn">
        <h2 class="text-base font-semibold text-navy-900">Lãi suất cao nhất theo kỳ hạn</h2>
        <ul class="mt-2 flex flex-wrap gap-2">
            @foreach ($landingTerms as $term)
                <li><a href="{{ route('compare.savings.term', $term) }}" class="inline-block rounded-full border border-slate-200 bg-white px-3 py-1 text-sm text-navy-900 hover:border-primary-300 hover:bg-primary-50">{{ $term }} tháng</a></li>
            @endforeach
        </ul>
    </nav>
@endif
