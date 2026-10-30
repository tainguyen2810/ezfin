{{-- Chọn biểu tượng và màu cho danh mục/mục tiêu (radio, chạy không cần JS). Truyền $model và $icons để dùng cho model khác. --}}
@php
    $model ??= $category;
    $icons ??= \App\Modules\Personal\Models\Category::ICONS;
@endphp
<fieldset>
    <legend class="mb-2 text-sm font-medium text-navy-900">Biểu tượng</legend>
    <div class="grid grid-cols-7 gap-1.5">
        @foreach ($icons as $icon)
            <label class="cursor-pointer">
                <input type="radio" name="icon" value="{{ $icon }}" class="peer sr-only" @checked(old('icon', $model->icon) === $icon)>
                <span class="flex aspect-square items-center justify-center rounded-lg border border-slate-200 text-slate-600 peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:text-primary-700 peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600"><x-ui.icon :name="$icon" class="size-5" /><span class="sr-only">{{ $icon }}</span></span>
            </label>
        @endforeach
    </div>
</fieldset>
<fieldset>
    <legend class="mb-2 text-sm font-medium text-navy-900">Màu</legend>
    <div class="flex flex-wrap gap-2">
        @foreach (\App\Modules\Personal\Models\Category::COLORS as $color => $classes)
            <label class="cursor-pointer">
                <input type="radio" name="color" value="{{ $color }}" class="peer sr-only" @checked(old('color', $model->color) === $color)>
                <span class="flex size-9 items-center justify-center rounded-full ring-offset-2 peer-checked:ring-2 peer-checked:ring-primary-600 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-600 {{ $classes }}"><x-ui.icon name="tag" class="size-4" /><span class="sr-only">{{ $color }}</span></span>
            </label>
        @endforeach
    </div>
</fieldset>
