@php
    $months = $goal->monthsLeft();
    $direction = old('direction', 'gop');
@endphp
<x-personal::shell :title="$goal->name">
    <x-slot:actions>
        <x-ui.button :href="route('personal.goals.edit', $goal)" icon="pencil-square" size="sm" variant="outline">Sửa</x-ui.button>
    </x-slot:actions>

    <div class="space-y-4">
        <a href="{{ route('personal.goals.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-primary-700"><x-ui.icon name="chevron-left" class="size-4" />Tất cả mục tiêu</a>

        {{-- Mobile: tổng quan, form ghi, lịch sử; desktop: form nằm cột phải. --}}
        <div class="grid items-start gap-4 lg:grid-cols-[1fr_360px]">
            <div class="contents lg:block lg:space-y-4">
                <x-ui.card class="order-1">
                    <div class="flex items-center gap-3">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl {{ $goal->colorClasses() }}"><x-ui.icon :name="$goal->icon" class="size-6" /></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-500">Đã để dành</p>
                            <p class="num text-2xl font-bold text-navy-900">{{ Format::money($goal->savedAmount()) }}</p>
                            <p class="num text-sm text-slate-500">trên {{ Format::money($goal->target()) }}</p>
                        </div>
                    </div>
                    <x-ui.progress class="mt-4" mode="goal" :value="$goal->savedAmount()" :max="$goal->target()" />
                    <dl class="mt-4 grid gap-2 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3 sm:gap-3">
                        <div class="flex items-baseline justify-between gap-2 sm:block"><dt class="text-xs text-slate-500">Còn thiếu</dt><dd class="num mt-0.5 font-semibold text-navy-900">{{ Format::money($goal->remaining()) }}</dd></div>
                        <div class="flex items-baseline justify-between gap-2 sm:block"><dt class="text-xs text-slate-500">Hạn</dt><dd class="num mt-0.5 font-semibold text-navy-900">{{ $goal->deadline ? Format::date($goal->deadline) : '—' }}</dd></div>
                        <div class="flex items-baseline justify-between gap-2 sm:block"><dt class="text-xs text-slate-500">Cần mỗi tháng</dt><dd class="num mt-0.5 font-semibold text-primary-700">{{ $goal->monthlyNeeded() !== null ? Format::money($goal->monthlyNeeded()) : '—' }}</dd></div>
                    </dl>
                    @if ($goal->isCompleted())
                        <x-ui.alert class="mt-4" variant="success" title="Bạn đã đạt mục tiêu">Có thể ghi khoản rút khi bạn dùng số tiền này.</x-ui.alert>
                    @elseif ($months === 0)
                        <x-ui.alert class="mt-4" variant="warning" title="Đã quá hạn">Sửa hạn hoàn thành nếu bạn vẫn đang để dành cho mục tiêu này.</x-ui.alert>
                    @endif
                </x-ui.card>

                <x-ui.card title="Lịch sử góp/rút" :padding="false" class="order-3">
                    @if ($contributions->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-slate-500">Chưa có lần góp nào. Ghi lần góp đầu tiên ở khung bên cạnh.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($contributions as $contribution)
                                @php($in = $contribution->amount > 0)
                                <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                                    <span @class(['flex size-9 shrink-0 items-center justify-center rounded-lg', 'bg-income-soft text-income' => $in, 'bg-expense-soft text-expense' => ! $in])><x-ui.icon :name="$in ? 'arrow-down-tray' : 'arrow-up-tray'" class="size-4" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-navy-900">{{ $contribution->note ?: ($in ? 'Góp thêm' : 'Rút bớt') }}</p>
                                        <p class="num text-xs text-slate-500">{{ Format::date($contribution->contributed_on) }}</p>
                                    </div>
                                    <p @class(['num shrink-0 font-semibold', 'text-income' => $in, 'text-expense' => ! $in])>{{ Format::signedMoney($contribution->amount) }}</p>
                                    <form method="POST" action="{{ route('personal.goals.contributions.destroy', [$goal, $contribution]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-expense" title="Xóa"><x-ui.icon name="trash" class="size-4" /><span class="sr-only">Xóa lần góp/rút ngày {{ Format::date($contribution->contributed_on) }}</span></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            </div>

            <x-ui.card title="Ghi góp/rút" class="order-2 lg:col-start-2 lg:row-start-1">
                <form method="POST" action="{{ route('personal.goals.contribute', $goal) }}" class="space-y-4">
                    @csrf
                    <fieldset>
                        <legend class="sr-only">Loại</legend>
                        <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                            @foreach (['gop' => 'Góp thêm', 'rut' => 'Rút bớt'] as $value => $label)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="direction" value="{{ $value }}" class="peer sr-only" @checked($direction === $value)>
                                    <span class="block rounded-lg px-3 py-2 text-center text-sm font-medium text-slate-600 peer-checked:bg-white peer-checked:text-primary-700 peer-checked:shadow-sm peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <x-ui.money-input name="amount" label="Số tiền" required />
                    <x-ui.input name="contributed_on" type="date" label="Ngày" :value="today()->toDateString()" required />
                    <x-ui.input name="note" label="Ghi chú" placeholder="Không bắt buộc" />
                    <x-ui.button type="submit" class="w-full">Lưu</x-ui.button>
                    <p class="text-xs text-slate-500">Ghi lại số bạn đã để riêng cho mục tiêu. Số dư ví không tự thay đổi; nếu chuyển sang ví tiết kiệm, hãy ghi thêm giao dịch chuyển tiền.</p>
                </form>
            </x-ui.card>

        </div>
    </div>
</x-personal::shell>
