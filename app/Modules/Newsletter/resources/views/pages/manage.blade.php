@php
    use App\Modules\Compare\Actions\BestSavingsRates;
    use App\Modules\Newsletter\Http\Controllers\SubscriptionController;

    $selected = $subscriber?->alerts->pluck('term_months')->all() ?? [];
@endphp
<x-layouts.public title="Quản lý email nhận tin" :noindex="true">
    <div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
        @if (session('newsletter'))
            <x-ui.alert variant="success" class="mb-4">{{ session('newsletter') }}</x-ui.alert>
        @endif
        <x-ui.card>
            <div class="p-5 sm:p-6">
                @if ($subscriber)
                    <h1 class="text-xl font-bold">Email nhận tin</h1>
                    <p class="mt-1 text-slate-600">Cho địa chỉ {{ SubscriptionController::maskEmail($subscriber->email) }}</p>
                    <form method="POST" action="{{ route('newsletter.manage', $token) }}" class="mt-5 space-y-5">
                        @csrf
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="ban_tin" value="1" @checked($subscriber->weekly_digest) class="mt-1 size-4 rounded border-slate-300 text-primary-600">
                            <span><span class="font-semibold text-navy-900">Bản tin tuần</span><br><span class="text-sm text-slate-500">Lãi suất cao nhất các kỳ hạn và bài viết mới, sáng thứ Hai.</span></span>
                        </label>
                        <fieldset>
                            <legend class="font-semibold text-navy-900">Báo khi lãi suất cao nhất tăng, kỳ hạn:</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (BestSavingsRates::TERMS as $term)
                                    <label class="flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-sm has-[:checked]:border-primary-400 has-[:checked]:bg-primary-50">
                                        <input type="checkbox" name="ky_han[]" value="{{ $term }}" @checked(in_array($term, $selected, true)) class="size-4 rounded border-slate-300 text-primary-600">
                                        {{ $term }} tháng
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-ui.button type="submit">Lưu lựa chọn</x-ui.button>
                    </form>
                    <form id="huy" method="POST" action="{{ route('newsletter.unsubscribe', $token) }}" class="mt-6 border-t border-slate-200 pt-5">
                        @csrf
                        <p class="text-sm text-slate-600">Không muốn nhận email nào nữa? EzFin sẽ xóa địa chỉ email của bạn.</p>
                        <x-ui.button type="submit" variant="outline" class="mt-3">Hủy nhận toàn bộ</x-ui.button>
                    </form>
                @else
                    <x-ui.empty-state icon="envelope" title="Không còn đăng ký nào"
                        description="Địa chỉ email này không nhận tin từ EzFin (đã hủy hoặc link không đúng). Bạn có thể đăng ký lại ở trang lãi suất.">
                        <x-ui.button :href="route('home')" variant="outline">Về trang chủ</x-ui.button>
                    </x-ui.empty-state>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-layouts.public>
