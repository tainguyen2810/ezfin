<x-layouts.public :title="$tool->title()" :description="$tool->summary()"
    :noindex="$customized" :canonical="route('planner.tools.show', $tool->slug())">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Công cụ tính' => route('planner.tools.index'), $tool->title() => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">{{ $tool->title() }}</h1>
        <p class="mt-1 max-w-3xl text-slate-600">{{ $tool->summary() }}</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[340px_1fr] lg:items-start">
            <x-ui.card>
                {{-- Form GET: chạy được khi tắt JS, URL kết quả chia sẻ được. --}}
                <form method="GET" action="{{ route('planner.tools.show', $tool->slug()) }}" class="space-y-4">
                    @include('planner::tools.'.$tool->slug().'.form', ['input' => $input])
                    <div class="flex gap-2 pt-1">
                        <x-ui.button type="submit" class="flex-1">Tính</x-ui.button>
                        @if ($customized)
                            <x-ui.button :href="route('planner.tools.show', $tool->slug())" variant="outline">Đặt lại</x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <div class="min-w-0 space-y-6" aria-live="polite">
                @if ($data)
                    @include('planner::tools.'.$tool->slug().'.result', $data)
                @elseif ($errors->has('tinh_toan'))
                    <x-ui.alert variant="warning">{{ $errors->first('tinh_toan') }}</x-ui.alert>
                @else
                    <x-ui.alert variant="warning">Số liệu chưa hợp lệ. Hãy sửa các ô được đánh dấu rồi bấm "Tính".</x-ui.alert>
                @endif

                @if ($data && $customized)
                    <x-ui.card>
                        <x-ui.share-link :url="$shareUrl" label="Liên kết đến kết quả này" />
                        <p class="mt-2 text-xs text-slate-500">Người nhận mở liên kết sẽ thấy đúng số liệu và kết quả như trên. EzFin không lưu số liệu bạn nhập.</p>
                    </x-ui.card>
                @endif

                <section aria-labelledby="cach-tinh">
                    <h2 id="cach-tinh" class="text-lg font-semibold">Cách tính và giả định</h2>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        @foreach ($tool->assumptions() as $assumption)
                            <li>{{ $assumption }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-slate-500">Kết quả chỉ để tham khảo, không phải khuyến nghị tài chính cá nhân. Số liệu thực tế phụ thuộc điều khoản của từng ngân hàng.</p>
                </section>
            </div>
        </div>
    </div>
</x-layouts.public>
