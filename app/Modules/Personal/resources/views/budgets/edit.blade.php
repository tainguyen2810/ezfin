<x-personal::shell title="Thiết lập ngân sách">
    <x-ui.card class="max-w-2xl">
        <p class="text-sm text-slate-600">Hạn mức chi cho từng danh mục trong <strong>tháng {{ $month->format('m/Y') }}</strong>. Để trống nếu không cần theo dõi danh mục đó.</p>
        <form method="POST" action="{{ route('personal.budgets.update') }}" class="mt-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="thang" value="{{ $month->format('Y-m') }}">
            <ul class="divide-y divide-slate-100">
                @foreach ($categories as $category)
                    <li class="flex items-center gap-3 py-2.5">
                        <x-personal::category-icon :category="$category" size="sm" />
                        <label for="amount-{{ $category->id }}" class="flex-1 text-sm font-medium text-navy-900">{{ $category->name }}</label>
                        <div class="w-40 sm:w-48">
                            <x-ui.money-input name="amounts[{{ $category->id }}]" id="amount-{{ $category->id }}" :value="$amounts[$category->id] ?? null" class="h-10" />
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Lưu ngân sách</x-ui.button>
                <x-ui.button :href="route('personal.budgets.index', ['thang' => $month->format('Y-m')])" variant="outline">Hủy</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-personal::shell>
