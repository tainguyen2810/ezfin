<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use DateTimeInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Chuyển trạng thái bài viết. Mỗi bước kiểm tra quyền của người thực hiện. */
class ArticleWorkflow
{
    /** Biên tập viên gửi bài nháp cho người duyệt. */
    public function submit(User $user, Article $article): void
    {
        Gate::forUser($user)->authorize('submit', $article);

        if ($errors = $article->publishingErrors()) {
            throw ValidationException::withMessages($errors);
        }

        $article->forceFill([
            'status' => ArticleStatus::InReview,
            'submitted_at' => now(),
            'review_note' => null,
        ])->save();
    }

    public function withdraw(User $user, Article $article): void
    {
        Gate::forUser($user)->authorize('withdraw', $article);

        $article->forceFill(['status' => ArticleStatus::Draft, 'submitted_at' => null])->save();
    }

    /** Duyệt và xuất bản ngay, hoặc hẹn giờ khi $at ở tương lai. */
    public function publish(User $user, Article $article, ?DateTimeInterface $at = null): void
    {
        Gate::forUser($user)->authorize('publish', $article);

        $article->forceFill([
            'status' => ArticleStatus::Published,
            'published_at' => $at ?? $article->published_at ?? now(),
            'reviewed_by' => $user->id,
            'review_note' => null,
        ])->save();
    }

    /** Trả bài về nháp kèm ghi chú cho biên tập viên. */
    public function return(User $user, Article $article, string $note): void
    {
        Gate::forUser($user)->authorize('publish', $article);

        $article->forceFill([
            'status' => ArticleStatus::Draft,
            'review_note' => $note,
            'submitted_at' => null,
        ])->save();
    }

    /** Gỡ bài khỏi website, giữ nguyên nội dung ở trạng thái nháp. */
    public function unpublish(User $user, Article $article): void
    {
        Gate::forUser($user)->authorize('publish', $article);

        $article->forceFill(['status' => ArticleStatus::Draft])->save();
    }
}
