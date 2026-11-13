<x-layouts.public title="Xác nhận nhận email" :noindex="true">
    <div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
        <x-ui.card>
            <div class="p-5 sm:p-6">
                @if ($request)
                    <h1 class="text-xl font-bold">Xác nhận nhận email từ EzFin</h1>
                    <p class="mt-2 text-slate-600">Địa chỉ <strong class="text-navy-900">{{ $request['email'] }}</strong> sẽ nhận:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-700">
                        @if ($request['term'])<li>Báo khi lãi suất tiết kiệm {{ $request['term'] }} tháng cao nhất thị trường tăng</li>@endif
                        @if ($request['weekly'])<li>Bản tin tuần vào sáng thứ Hai</li>@endif
                    </ul>
                    <form method="POST" action="{{ route('newsletter.confirm', $payload) }}" class="mt-5">
                        @csrf
                        <x-ui.button type="submit">Xác nhận đăng ký</x-ui.button>
                    </form>
                    <p class="mt-4 text-xs text-slate-500">Không phải bạn đăng ký? Chỉ cần đóng trang này, EzFin sẽ không lưu email.</p>
                @else
                    <x-ui.empty-state icon="exclamation-triangle" title="Link xác nhận không hợp lệ hoặc đã hết hạn"
                        description="Link chỉ dùng được {{ \App\Modules\Newsletter\Actions\RequestSubscription::LINK_DAYS }} ngày. Hãy đăng ký lại để nhận link mới.">
                        <x-ui.button :href="route('home')" variant="outline">Về trang chủ</x-ui.button>
                    </x-ui.empty-state>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-layouts.public>
