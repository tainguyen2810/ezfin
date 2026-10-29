<x-layouts.public title="Công cụ tính toán tài chính"
    description="Công cụ tính lãi kép, lãi tiền gửi, khoản vay miễn phí cho người Việt. Nêu rõ cách tính và giả định.">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Công cụ tính' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Công cụ tính toán tài chính</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Nhập số liệu của bạn để xem kết quả ngay, kèm cách tính và giả định. Không cần đăng nhập, không lưu số liệu.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($tools as $tool)
                <a href="{{ route('planner.tools.show', $tool->slug()) }}"
                    class="group flex gap-4 rounded-card border border-slate-200 bg-white p-5 shadow-card transition hover:border-primary-300">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <x-ui.icon :name="$tool->icon()" class="size-6" />
                    </span>
                    <span>
                        <span class="block font-semibold text-navy-900 group-hover:text-primary-700">{{ $tool->title() }}</span>
                        <span class="mt-1 block text-sm text-slate-600">{{ $tool->summary() }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.public>
