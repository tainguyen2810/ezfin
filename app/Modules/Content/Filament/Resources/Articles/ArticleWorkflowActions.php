<?php

namespace App\Modules\Content\Filament\Resources\Articles;

use App\Modules\Content\Actions\ArticleWorkflow;
use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Nút chuyển trạng thái bài viết trên trang xem và trang sửa. Mỗi nút chỉ hiện khi người dùng có quyền;
 * ArticleWorkflow kiểm tra quyền thêm một lần khi thực hiện.
 */
class ArticleWorkflowActions
{
    /** @return list<Action> */
    public static function make(): array
    {
        return [
            Action::make('viewOnSite')->label('Xem trên website')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Article $record) => $record->url(), shouldOpenInNewTab: true)
                ->visible(fn (Article $record) => $record->isPublic()),

            Action::make('submit')->label('Gửi duyệt')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (Article $record) => auth()->user()->can('submit', $record))
                ->requiresConfirmation()
                ->modalDescription('Bài sẽ được khóa sửa cho tới khi người duyệt xuất bản hoặc trả lại. Thay đổi chưa lưu sẽ được lưu trước.')
                ->action(self::run(fn (ArticleWorkflow $workflow, Article $record) => $workflow->submit(auth()->user(), $record), 'Đã gửi duyệt.')),

            Action::make('withdraw')->label('Rút về nháp')
                ->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                ->visible(fn (Article $record) => auth()->user()->can('withdraw', $record))
                ->requiresConfirmation()
                ->action(self::run(fn (ArticleWorkflow $workflow, Article $record) => $workflow->withdraw(auth()->user(), $record), 'Đã rút bài về nháp.', save: false)),

            Action::make('publish')->label('Duyệt và xuất bản')
                ->icon(Heroicon::OutlinedCheckBadge)->color('success')
                ->visible(fn (Article $record) => $record->status !== ArticleStatus::Published && ! $record->trashed()
                    && auth()->user()->can('publish', $record))
                ->schema([
                    DateTimePicker::make('published_at')->label('Thời điểm xuất bản')
                        ->native(false)->displayFormat('d/m/Y H:i')->seconds(false)
                        ->default(fn (Article $record) => $record->published_at ?? now())
                        ->required()
                        ->helperText('Chọn giờ trong tương lai để hẹn giờ đăng.'),
                ])
                ->action(self::run(
                    fn (ArticleWorkflow $workflow, Article $record, array $data) => $workflow->publish(auth()->user(), $record, Carbon::parse($data['published_at'])),
                    'Đã xuất bản.',
                )),

            Action::make('return')->label('Trả lại')
                ->icon(Heroicon::OutlinedArrowUturnDown)->color('warning')
                ->visible(fn (Article $record) => $record->status === ArticleStatus::InReview && auth()->user()->can('publish', $record))
                ->schema([
                    Textarea::make('note')->label('Cần sửa gì')->required()->maxLength(2000)->rows(4),
                ])
                ->action(self::run(
                    fn (ArticleWorkflow $workflow, Article $record, array $data) => $workflow->return(auth()->user(), $record, $data['note']),
                    'Đã trả bài về nháp kèm ghi chú.',
                    save: false,
                )),

            Action::make('unpublish')->label('Gỡ xuất bản')
                ->icon(Heroicon::OutlinedEyeSlash)->color('danger')
                ->visible(fn (Article $record) => $record->status === ArticleStatus::Published && ! $record->trashed()
                    && auth()->user()->can('publish', $record))
                ->requiresConfirmation()
                ->modalDescription('Bài sẽ không còn hiển thị trên website và quay về trạng thái nháp.')
                ->action(self::run(fn (ArticleWorkflow $workflow, Article $record) => $workflow->unpublish(auth()->user(), $record), 'Đã gỡ bài khỏi website.', save: false)),
        ];
    }

    /**
     * Lưu form đang sửa (nếu có), chạy bước chuyển trạng thái, rồi tải lại trang phù hợp với quyền mới.
     */
    private static function run(Closure $step, string $success, bool $save = true): Closure
    {
        return function (Article $record, Page $livewire, Action $action, array $data = []) use ($step, $success, $save) {
            if ($save && $livewire instanceof EditRecord) {
                $livewire->save(shouldRedirect: false, shouldSendSavedNotification: false);
                $record->refresh();
            }

            try {
                $step(app(ArticleWorkflow::class), $record, $data);
            } catch (ValidationException $exception) {
                Notification::make()->danger()->title('Chưa thực hiện được')
                    ->body(implode(' ', $exception->validator->errors()->all()))
                    ->send();

                $action->halt();
            }

            Notification::make()->success()->title($success)->send();

            $livewire->redirect(ArticleResource::getUrl(
                auth()->user()->can('update', $record) ? 'edit' : 'view',
                ['record' => $record],
            ));
        };
    }
}
