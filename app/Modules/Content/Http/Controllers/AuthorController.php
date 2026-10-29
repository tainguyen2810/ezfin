<?php

namespace App\Modules\Content\Http\Controllers;

use App\Modules\Content\Models\Author;
use Illuminate\View\View;

class AuthorController
{
    public function show(string $slug): View
    {
        $author = Author::query()->active()->where('slug', $slug)->firstOrFail();

        return view('content::authors.show', [
            'author' => $author,
            'articles' => $author->articles()->published()
                ->with(['category', 'author'])
                ->latest('published_at')
                ->paginate(ArticleController::PER_PAGE),
        ]);
    }
}
