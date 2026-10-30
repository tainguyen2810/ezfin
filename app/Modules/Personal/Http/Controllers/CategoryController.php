<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController
{
    public function index(Request $request, CreateDefaultCategories $defaults): View
    {
        $defaults->handle($request->user());

        return view('personal::categories.index', [
            'groups' => Category::active()->orderBy('sort')->orderBy('name')->get()->groupBy(fn (Category $c) => $c->type->value),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, creating: true);
        Category::create([...$data, 'sort' => 100]);

        return redirect()->route('personal.categories.index')->with('toast', ['message' => 'Đã thêm danh mục.', 'type' => 'success']);
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('personal::categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);
        $category->update($this->validated($request, creating: false));

        return redirect()->route('personal.categories.index')->with('toast', ['message' => 'Đã lưu thay đổi.', 'type' => 'success']);
    }

    /** Ẩn danh mục; giao dịch cũ vẫn giữ danh mục để báo cáo đúng. */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);
        $category->forceFill(['archived_at' => now()])->save();

        return redirect()->route('personal.categories.index')->with('toast', ['message' => 'Đã ẩn danh mục "'.$category->name.'".', 'type' => 'success']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => [$creating ? 'required' : 'prohibited', Rule::enum(CategoryType::class)],
            'icon' => ['required', Rule::in(Category::ICONS)],
            'color' => ['required', Rule::in(array_keys(Category::COLORS))],
        ], [], ['name' => 'tên danh mục', 'icon' => 'biểu tượng', 'color' => 'màu']);
    }
}
