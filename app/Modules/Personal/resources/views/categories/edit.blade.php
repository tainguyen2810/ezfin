<x-personal::shell title="Sửa danh mục">
    <x-ui.card class="max-w-xl">
        <p class="mb-4 text-sm text-slate-500">Loại: {{ $category->type->label() }}</p>
        <form method="POST" action="{{ route('personal.categories.update', $category) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-ui.input name="name" label="Tên danh mục" :value="$category->name" required />
            @include('personal::categories._appearance')
            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit">Lưu thay đổi</x-ui.button>
                <x-ui.button :href="route('personal.categories.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>
        <form method="POST" action="{{ route('personal.categories.destroy', $category) }}" class="mt-6 border-t border-slate-100 pt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="flex items-center gap-2 text-sm font-medium text-expense hover:underline"><x-ui.icon name="trash" class="size-4" />Ẩn danh mục</button>
            <p class="mt-1 text-xs text-slate-500">Giao dịch cũ vẫn giữ danh mục này trong lịch sử và báo cáo.</p>
        </form>
    </x-ui.card>
</x-personal::shell>
