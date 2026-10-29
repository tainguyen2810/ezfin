<?php

namespace App\Modules\Content\Policies;

use App\Models\User;
use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use App\Modules\Core\Enums\Role;

/**
 * Biên tập viên viết và sửa bài nháp, gửi duyệt; không tự xuất bản và không sửa bài đang chờ duyệt
 * hay đã xuất bản. Quản trị cao nhất duyệt, xuất bản, gỡ bài, xóa.
 */
class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdmin->value, Role::Editor->value]);
    }

    public function view(User $user, Article $article): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Article $article): bool
    {
        if ($article->trashed()) {
            return false;
        }

        return $user->isSuperAdmin()
            || ($user->hasRole(Role::Editor->value) && $article->status === ArticleStatus::Draft);
    }

    public function submit(User $user, Article $article): bool
    {
        return $article->status === ArticleStatus::Draft && $this->update($user, $article);
    }

    /** Rút bài đang chờ duyệt về nháp để sửa tiếp. */
    public function withdraw(User $user, Article $article): bool
    {
        return $article->status === ArticleStatus::InReview && ! $article->trashed() && $this->viewAny($user);
    }

    /** Xuất bản, trả lại, gỡ bài và sửa bài đã xuất bản. */
    public function publish(User $user, ?Article $article = null): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Article $article): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Article $article): bool
    {
        return false;
    }
}
