@php $editing = $goal->exists; @endphp
<x-personal::shell :title="$editing ? 'Sửa mục tiêu' : 'Thêm mục tiêu'">
    <x-ui.card class="max-w-xl">
        <form method="POST" action="{{ $editing ? route('personal.goals.update', $goal) : route('personal.goals.store') }}" class="space-y-4">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-ui.input name="name" label="Tên mục tiêu" :value="$goal->name" placeholder="Ví dụ: Mua nhà, Du lịch Nhật Bản, Quỹ dự phòng" required />
            <x-ui.money-input name="target_amount" label="Số tiền cần" :value="$goal->target_amount" required />
            @unless ($editing)
                <x-ui.money-input name="initial_amount" label="Số tiền đã có" hint="Không bắt buộc. Số bạn đã để dành sẵn cho mục tiêu này." />
            @endunless
            <x-ui.input name="deadline" type="date" label="Hạn hoàn thành" :value="$goal->deadline?->toDateString()" hint="Không bắt buộc. Có hạn thì EzFin tính số cần để dành mỗi tháng." />
            @include('personal::categories._appearance', ['model' => $goal, 'icons' => \App\Modules\Personal\Models\Goal::ICONS])
            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit">{{ $editing ? 'Lưu thay đổi' : 'Tạo mục tiêu' }}</x-ui.button>
                <x-ui.button :href="$editing ? route('personal.goals.show', $goal) : route('personal.goals.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>

        @if ($editing)
            <div class="mt-6 border-t border-slate-100 pt-4" x-data>
                <button type="button" x-on:click="$dispatch('open-modal', 'xoa-muc-tieu')" class="flex items-center gap-2 text-sm font-medium text-expense hover:underline"><x-ui.icon name="trash" class="size-4" />Xóa mục tiêu</button>
            </div>
            <x-ui.modal name="xoa-muc-tieu" title="Xóa mục tiêu này?">
                Mục tiêu và lịch sử góp/rút sẽ bị xóa hẳn. Số dư các ví không thay đổi.
                <x-slot:footer>
                    <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Giữ lại</x-ui.button>
                    <form method="POST" action="{{ route('personal.goals.destroy', $goal) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" class="w-full">Xóa mục tiêu</x-ui.button>
                    </form>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    </x-ui.card>
</x-personal::shell>
