<x-layouts.public title="Công cụ tính toán tài chính"
    description="Công cụ tính lãi kép, lãi tiền gửi, khoản vay miễn phí cho người Việt. Nêu rõ cách tính và giả định.">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-ui.breadcrumb :items="['Trang chủ' => route('home'), 'Công cụ tính' => null]" />
        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Công cụ tính toán tài chính</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Nhập số liệu của bạn để xem kết quả ngay, kèm cách tính và giả định. Không cần đăng nhập, không lưu số liệu.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tools as $tool)
                <a href="{{ route('planner.tools.show', $tool->slug()) }}"
                    class="group flex flex-col rounded-card border border-slate-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-lg">
                    <div class="p-4 pb-0">
                        <div class="flex h-24 items-center justify-center rounded-xl bg-gradient-to-br from-slate-50 to-primary-50">
                            <span class="flex size-14 items-center justify-center rounded-2xl bg-white text-primary-600 shadow-card transition group-hover:scale-105">
                                <x-ui.icon :name="$tool->icon()" class="size-7" />
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 p-4">
                        <h2 class="font-semibold text-navy-900 group-hover:text-primary-700">{{ $tool->title() }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $tool->summary() }}</p>
                    </div>
                    <span class="flex items-center gap-1 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-primary-700">
                        Tính ngay <x-ui.icon name="chevron-right" class="size-4" />
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.public>
