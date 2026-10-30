@php
    $editing = $transaction->exists;
    $tabUrl = fn (string $slug) => $editing ? route('personal.transactions.edit', [$transaction, 'loai' => $slug]) : route('personal.transactions.create', ['loai' => $slug]);
    $selectedCategory = (int) old('category_id', $transaction->category_id);
@endphp
<x-personal::shell :title="$editing ? 'Sửa giao dịch' : 'Thêm giao dịch'">
    <x-ui.card class="mx-auto max-w-xl">
        <nav class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 text-sm font-semibold" aria-label="Loại giao dịch">
            @foreach (['thu' => 'Thu nhập', 'chi' => 'Chi tiêu', 'chuyen' => 'Chuyển tiền'] as $slug => $label)
                <a href="{{ $tabUrl($slug) }}" @class(['whitespace-nowrap rounded-lg px-2 py-2 text-center', 'bg-white text-primary-700 shadow-sm' => $types[$type] === $slug, 'text-slate-600 hover:text-navy-900' => $types[$type] !== $slug])
                    @if ($types[$type] === $slug) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ $editing ? route('personal.transactions.update', $transaction) : route('personal.transactions.store') }}" class="mt-5 space-y-4">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="type" value="{{ $type }}">

            <x-ui.money-input name="amount" label="Số tiền" :value="$transaction->amount" class="h-14 text-2xl font-bold" required autofocus />

            @if ($type === 'transfer')
                <x-ui.select name="from_wallet_id" label="Từ ví" :options="$wallets" :value="$fromWalletId" placeholder="Chọn ví" required />
                <x-ui.select name="to_wallet_id" label="Đến ví" :options="$wallets" :value="$toWalletId" placeholder="Chọn ví" required />
                <p class="text-xs text-slate-500">Chuyển tiền giữa các ví của bạn không tính là thu nhập hay chi tiêu.</p>
            @else
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-navy-900">Danh mục <span class="text-red-600">*</span></legend>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                        @foreach ($categories as $category)
                            <label class="cursor-pointer">
                                <input type="radio" name="category_id" value="{{ $category->id }}" class="peer sr-only" @checked($selectedCategory === $category->id) required>
                                <span class="flex h-full flex-col items-center gap-1.5 rounded-xl border border-slate-200 p-2 text-center text-xs font-medium text-slate-700 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:text-primary-700 peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600">
                                    <x-personal::category-icon :category="$category" size="sm" />{{ $category->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <a href="{{ route('personal.categories.index') }}" class="mt-2 inline-block text-xs font-medium text-primary-700 hover:underline">Thêm hoặc sửa danh mục</a>
                </fieldset>
                <x-ui.select name="wallet_id" label="Ví / tài khoản" :options="$wallets" :value="$transaction->wallet_id" placeholder="Chọn ví" required />
            @endif

            <x-ui.input name="occurred_on" type="date" label="Ngày" :value="$transaction->occurred_on?->toDateString()" required />
            <x-ui.input name="note" label="Ghi chú" :value="$transaction->note" placeholder="Ví dụ: Lương tháng 10, ăn trưa" />

            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit" class="flex-1 sm:flex-none">{{ $editing ? 'Lưu thay đổi' : 'Lưu giao dịch' }}</x-ui.button>
                <x-ui.button :href="route('personal.transactions.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>

        @if ($editing)
            <div class="mt-6 border-t border-slate-100 pt-4" x-data>
                <button type="button" x-on:click="$dispatch('open-modal', 'xoa-giao-dich')" class="flex items-center gap-2 text-sm font-medium text-expense hover:underline"><x-ui.icon name="trash" class="size-4" />Xóa giao dịch</button>
            </div>
            <x-ui.modal name="xoa-giao-dich" title="Xóa giao dịch này?">
                {{ $transaction->transfer_group_id ? 'Cả hai vế của lần chuyển tiền sẽ bị xóa và số dư hai ví được tính lại.' : 'Số dư ví sẽ được tính lại. Không hoàn tác được.' }}
                <x-slot:footer>
                    <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Giữ lại</x-ui.button>
                    <form method="POST" action="{{ route('personal.transactions.destroy', $transaction) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" class="w-full">Xóa</x-ui.button>
                    </form>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    </x-ui.card>
</x-personal::shell>
