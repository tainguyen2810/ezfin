@php
    $editing = $item->exists;
    $noun = $debt ? 'khoản nợ' : 'tài sản';
@endphp
<x-personal::shell :title="($editing ? 'Sửa ' : 'Thêm ').$noun">
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ $editing ? route('personal.net-worth.update', $item) : route('personal.net-worth.store') }}" class="space-y-4">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-ui.select name="kind" label="Loại" :options="\App\Modules\Personal\Enums\NetWorthKind::options($debt)" :value="$item->kind?->value" required />
            <x-ui.input name="name" label="Tên" :value="$item->name" :placeholder="$debt ? 'Ví dụ: Vay mua nhà Vietcombank' : 'Ví dụ: Căn hộ Thủ Đức, Xe máy, 2 chỉ vàng'" required />
            <x-ui.money-input name="amount" :label="$debt ? 'Dư nợ còn lại' : 'Giá trị ước tính'" :value="$item->amount" required
                :hint="$debt ? 'Số tiền gốc còn phải trả.' : 'Giá bạn ước tính có thể bán được hôm nay.'" />
            @if ($debt)
                <x-ui.input name="interest_rate" label="Lãi suất (%/năm)" inputmode="decimal" :value="$item->interest_rate === null ? null : Format::decimal((float) $item->interest_rate, 4)" placeholder="Ví dụ: 9,5" hint="Không bắt buộc." />
                <x-ui.money-input name="monthly_payment" label="Số trả mỗi tháng" :value="$item->monthly_payment" hint="Không bắt buộc. Gồm cả gốc và lãi." />
            @endif
            <x-ui.input name="valued_on" type="date" label="Ngày cập nhật" :value="$item->valued_on?->toDateString()" required />
            <x-ui.input name="note" label="Ghi chú" :value="$item->note" placeholder="Không bắt buộc" />
            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit">{{ $editing ? 'Lưu thay đổi' : 'Thêm '.$noun }}</x-ui.button>
                <x-ui.button :href="route('personal.net-worth.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>

        @if ($editing)
            <div class="mt-6 border-t border-slate-100 pt-4" x-data>
                <button type="button" x-on:click="$dispatch('open-modal', 'xoa-muc')" class="flex items-center gap-2 text-sm font-medium text-expense hover:underline"><x-ui.icon name="trash" class="size-4" />Xóa {{ $noun }}</button>
            </div>
            <x-ui.modal name="xoa-muc" :title="'Xóa '.$noun.' này?'">
                "{{ $item->name }}" sẽ không còn được tính vào tài sản ròng.
                <x-slot:footer>
                    <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Giữ lại</x-ui.button>
                    <form method="POST" action="{{ route('personal.net-worth.destroy', $item) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" class="w-full">Xóa</x-ui.button>
                    </form>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    </x-ui.card>
</x-personal::shell>
