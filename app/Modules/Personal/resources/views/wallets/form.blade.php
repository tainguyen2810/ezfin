@php $editing = $wallet->exists; @endphp
<x-personal::shell :title="$editing ? 'Sửa ví' : 'Thêm ví'">
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ $editing ? route('personal.wallets.update', $wallet) : route('personal.wallets.store') }}" class="space-y-4">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-ui.input name="name" label="Tên ví" :value="$wallet->name" placeholder="Ví dụ: Tiền mặt, Tài khoản lương" required />
            <x-ui.select name="type" label="Loại" :options="\App\Modules\Personal\Enums\WalletType::options()" :value="$wallet->type?->value" required />
            <x-ui.input name="institution" label="Ngân hàng / nhà cung cấp" :value="$wallet->institution" placeholder="Ví dụ: Vietcombank, MoMo" hint="Không bắt buộc. Không nhập số tài khoản." />
            <x-ui.money-input name="opening_balance" label="Số dư ban đầu" :value="$wallet->opening_balance" hint="Số tiền đang có trong ví lúc bắt đầu theo dõi." required />
            <x-ui.toggle name="include_in_total" label="Tính vào tổng tài sản" :checked="$wallet->include_in_total" description="Tắt nếu đây là tiền giữ hộ hoặc không muốn cộng vào tổng." />
            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit">{{ $editing ? 'Lưu thay đổi' : 'Thêm ví' }}</x-ui.button>
                <x-ui.button :href="route('personal.wallets.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>

        @if ($editing)
            <div class="mt-6 border-t border-slate-100 pt-4" x-data>
                <button type="button" x-on:click="$dispatch('open-modal', 'an-vi')" class="flex items-center gap-2 text-sm font-medium text-expense hover:underline"><x-ui.icon name="trash" class="size-4" />Ẩn ví</button>
            </div>
            <x-ui.modal name="an-vi" title="Ẩn ví này?">
                Ví bị ẩn không còn hiện trong danh sách và không được tính vào tổng. Lịch sử giao dịch của ví vẫn được giữ.
                <x-slot:footer>
                    <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Giữ lại</x-ui.button>
                    <form method="POST" action="{{ route('personal.wallets.destroy', $wallet) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" class="w-full">Ẩn ví</x-ui.button>
                    </form>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    </x-ui.card>
</x-personal::shell>
