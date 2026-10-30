<x-personal::shell title="Mục tiêu">
    <x-slot:actions>
        <div class="hidden sm:block"><x-ui.button :href="route('personal.goals.create')" icon="plus" size="sm">Thêm mục tiêu</x-ui.button></div>
    </x-slot:actions>

    <div class="space-y-4">
        <x-ui.tabs label="Trạng thái mục tiêu" class="max-w-md" :items="[
            ['label' => 'Đang thực hiện ('.$activeCount.')', 'href' => route('personal.goals.index'), 'active' => ! $completedTab],
            ['label' => 'Đã hoàn thành ('.$completedCount.')', 'href' => route('personal.goals.index', ['trang_thai' => 'hoan-thanh']), 'active' => $completedTab],
        ]" />

        @if (! $completedTab && $activeCount > 0)
            <x-ui.card>
                <div class="grid grid-cols-3 gap-3">
                    <div><p class="text-xs text-slate-500 sm:text-sm">Cần tích lũy</p><p class="num mt-1 font-bold text-navy-900 sm:text-xl"><span class="sm:hidden">{{ Format::compactMoney($target) }}</span><span class="hidden sm:inline">{{ Format::money($target) }}</span></p></div>
                    <div><p class="text-xs text-slate-500 sm:text-sm">Đã để dành</p><p class="num mt-1 font-bold text-income sm:text-xl"><span class="sm:hidden">{{ Format::compactMoney($saved) }}</span><span class="hidden sm:inline">{{ Format::money($saved) }}</span></p></div>
                    <div><p class="text-xs text-slate-500 sm:text-sm">Cần mỗi tháng</p><p class="num mt-1 font-bold text-primary-700 sm:text-xl"><span class="sm:hidden">{{ Format::compactMoney($monthly) }}</span><span class="hidden sm:inline">{{ Format::money($monthly) }}</span></p></div>
                </div>
                <x-ui.progress class="mt-4" mode="goal" :value="$saved" :max="$target" />
            </x-ui.card>
        @endif

        @if ($goals->isEmpty())
            <x-ui.card>
                @if ($completedTab)
                    <x-ui.empty-state icon="trophy" title="Chưa có mục tiêu hoàn thành" description="Mục tiêu sẽ chuyển sang đây khi số đã để dành đạt số tiền cần." />
                @else
                    <x-ui.empty-state icon="flag" title="Chưa có mục tiêu nào"
                        description="Đặt mục tiêu như mua nhà, du lịch hay quỹ dự phòng. EzFin tính giúp bạn cần để dành bao nhiêu mỗi tháng để kịp hạn.">
                        <x-ui.button :href="route('personal.goals.create')" icon="plus">Thêm mục tiêu</x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @else
            <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($goals as $goal)
                    <li>
                        <a href="{{ route('personal.goals.show', $goal) }}" class="block h-full rounded-card border border-slate-200 bg-white p-4 shadow-card transition hover:border-primary-300 focus-visible:outline-2 focus-visible:outline-primary-600">
                            <div class="flex items-start gap-3">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $goal->colorClasses() }}"><x-ui.icon :name="$goal->icon" class="size-6" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-navy-900">{{ $goal->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $goal->deadline ? 'Hạn '.Format::date($goal->deadline) : 'Không đặt hạn' }}</p>
                                </div>
                            </div>
                            <p class="num mt-4 text-sm"><span class="text-lg font-bold text-navy-900">{{ Format::money($goal->savedAmount()) }}</span> <span class="text-slate-500">/ {{ Format::money($goal->target()) }}</span></p>
                            <x-ui.progress class="mt-2" mode="goal" :value="$goal->savedAmount()" :max="$goal->target()" />
                            <p class="mt-2 text-xs text-slate-500">@include('personal::goals._status')</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-ui.button :href="route('personal.goals.create')" icon="plus" variant="outline" class="w-full sm:hidden">Thêm mục tiêu</x-ui.button>
        <p class="text-xs text-slate-500">Số đã để dành là tổng các lần bạn ghi góp/rút cho mục tiêu, không tự trừ vào số dư ví. Đây là công cụ theo dõi, không phải khuyến nghị đầu tư.</p>
    </div>
</x-personal::shell>
