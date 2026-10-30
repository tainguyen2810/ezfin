@php use App\Modules\Personal\Enums\CategoryType; use App\Modules\Personal\Models\Category; @endphp
<x-personal::shell title="Danh mục thu chi">
    <div class="grid gap-5 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-5">
            @foreach ([CategoryType::Expense, CategoryType::Income] as $type)
                <x-ui.card :title="$type->label()">
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($groups[$type->value] ?? [] as $category)
                            <li class="flex items-center gap-3 rounded-lg border border-slate-100 p-2">
                                <x-personal::category-icon :category="$category" size="sm" />
                                <span class="flex-1 truncate text-sm font-medium text-navy-900">{{ $category->name }}</span>
                                <a href="{{ route('personal.categories.edit', $category) }}" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-navy-900"><x-ui.icon name="pencil-square" class="size-4" /><span class="sr-only">Sửa {{ $category->name }}</span></a>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endforeach
        </div>

        <x-ui.card title="Thêm danh mục" class="h-fit">
            <form method="POST" action="{{ route('personal.categories.store') }}" class="space-y-4">
                @csrf
                <x-ui.select name="type" label="Loại" :options="[CategoryType::Expense->value => 'Chi tiêu', CategoryType::Income->value => 'Thu nhập']" required />
                <x-ui.input name="name" label="Tên danh mục" required />
                @include('personal::categories._appearance', ['category' => new Category(['icon' => 'tag', 'color' => 'blue'])])
                <x-ui.button type="submit" class="w-full">Thêm</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-personal::shell>
