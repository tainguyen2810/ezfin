@php
    $editing = $item->exists;
    $tabUrl = fn (string $slug) => $editing ? route('personal.recurring.edit', [$item, 'loai' => $slug]) : route('personal.recurring.create', ['loai' => $slug]);
    $selectedCategory = (int) old('category_id', $item->category_id);
@endphp
<x-personal::shell :title="$editing ? 'Sửa khoản định kỳ' : 'Thêm khoản định kỳ'">
    <x-ui.card class="mx-auto max-w-xl">
        <nav class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 text-sm font-semibold" aria-label="Loại khoản">
            @foreach (['chi' => 'Khoản chi', 'thu' => 'Khoản thu'] as $slug => $label)
                <a href="{{ $tabUrl($slug) }}" @class(['whitespace-nowrap rounded-lg px-2 py-2 text-center', 'bg-white text-primary-700 shadow-sm' => $types[$type] === $slug, 'text-slate-600 hover:text-navy-900' => $types[$type] !== $slug])
                    @if ($types[$type] === $slug) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ $editing ? route('personal.recurring.update', $item) : route('personal.recurring.store') }}" class="mt-5 space-y-4">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="type" value="{{ $type }}">

            <x-ui.input name="name" label="Tên khoản" :value="$item->name" :placeholder="$type === 'income' ? 'Ví dụ: Lương công ty' : 'Ví dụ: Trả thẻ tín dụng VIB, tiền nhà'" required maxlength="100" />
            <x-ui.money-input name="amount" label="Số tiền mỗi kỳ" :value="$item->amount" required />

            <fieldset>
                <legend class="mb-2 text-sm font-medium text-navy-900">Danh mục</legend>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                    @foreach ($categories as $category)
                        <label class="cursor-pointer">
                            <input type="radio" name="category_id" value="{{ $category->id }}" class="peer sr-only" @checked($selectedCategory === $category->id)>
                            <span class="flex h-full flex-col items-center gap-1.5 rounded-xl border border-slate-200 p-2 text-center text-xs font-medium text-slate-700 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:text-primary-700 peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600">
                                <x-personal::category-icon :category="$category" size="sm" />{{ $category->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>

            <x-ui.select name="wallet_id" label="Ví / tài khoản" :options="$wallets" :value="$item->wallet_id" placeholder="Chọn ví"
                hint="Ví và danh mục dùng để ghi giao dịch; khoản chỉ nhắc hạn có thể bỏ trống." />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select name="frequency" label="Lặp lại" :options="$frequencies" :value="$item->frequency?->value" required />
                <x-ui.input name="starts_on" type="date" label="Ngày đến hạn đầu tiên" :value="$item->starts_on?->toDateString()" required />
            </div>
            <x-ui.input name="ends_on" type="date" label="Ngày kết thúc (không bắt buộc)" :value="$item->ends_on?->toDateString()"
                hint="Ví dụ ngày trả góp kỳ cuối. Bỏ trống nếu lặp mãi." />

            <div class="space-y-4 rounded-xl bg-slate-50 p-4">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-navy-900">Khi đến hạn</legend>
                    <div class="space-y-2">
                        @foreach ($modes as $mode)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50">
                                <input type="radio" name="mode" value="{{ $mode->value }}" class="mt-1 size-4 text-primary-600 focus:ring-primary-600"
                                    @checked(old('mode', $item->mode?->value) === $mode->value) required>
                                <span>
                                    <span class="block text-sm font-medium text-navy-900">{{ $mode->label() }}</span>
                                    <span class="block text-xs text-slate-500">{{ $mode->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </fieldset>
                <x-ui.select name="remind_days_before" label="Email nhắc hạn" :options="$remindOptions" :value="$item->remind_days_before"
                    placeholder="Không gửi email" />
            </div>

            @if ($editing && $item->next_due_on)
                <p class="text-sm text-slate-500">Kỳ tiếp theo: <strong class="num text-navy-900">{{ Format::date($item->next_due_on) }}</strong>. Đổi lịch thì kỳ tiếp theo được tính lại từ hôm nay.</p>
            @endif

            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit" class="flex-1 sm:flex-none">{{ $editing ? 'Lưu thay đổi' : 'Lưu khoản định kỳ' }}</x-ui.button>
                <x-ui.button :href="route('personal.recurring.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>

        @if ($editing)
            <div class="mt-6 border-t border-slate-100 pt-4" x-data>
                <button type="button" x-on:click="$dispatch('open-modal', 'xoa-dinh-ky')" class="flex items-center gap-2 text-sm font-medium text-expense hover:underline"><x-ui.icon name="trash" class="size-4" />Xóa khoản định kỳ</button>
            </div>
            <x-ui.modal name="xoa-dinh-ky" title="Xóa khoản định kỳ này?">
                EzFin sẽ ngừng ghi và nhắc khoản này. Các giao dịch đã ghi trước đó vẫn được giữ.
                <x-slot:footer>
                    <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Giữ lại</x-ui.button>
                    <form method="POST" action="{{ route('personal.recurring.destroy', $item) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" class="w-full">Xóa</x-ui.button>
                    </form>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    </x-ui.card>
</x-personal::shell>
