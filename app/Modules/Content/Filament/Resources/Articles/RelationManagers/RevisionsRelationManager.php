<?php

namespace App\Modules\Content\Filament\Resources\Articles\RelationManagers;

use App\Modules\Content\Filament\Resources\Articles\ArticleResource;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleRevision;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Lịch sử sửa: xem lại từng bản và khôi phục (tạo thêm một bản mới, không xóa bản nào). */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Lịch sử sửa';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextEntry::make('title')->label('Tiêu đề'),
            TextEntry::make('excerpt')->label('Tóm tắt')->placeholder('—'),
            TextEntry::make('body')->label('Nội dung')->html()->placeholder('—')->prose(),
            RepeatableEntry::make('references')->label('Nguồn tham khảo')->placeholder('—')
                ->schema([TextEntry::make('title')->hiddenLabel(), TextEntry::make('url')->hiddenLabel()->placeholder('—')])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('bản lưu')
            ->columns([
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i:s'),
                TextColumn::make('user.name')->label('Người sửa')->placeholder('Hệ thống'),
                TextColumn::make('title')->label('Tiêu đề')->limit(60),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make()->modalHeading('Bản lưu'),
                Action::make('restore')->label('Khôi phục')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->visible(fn () => auth()->user()->can('update', $this->getOwnerRecord()))
                    ->requiresConfirmation()
                    ->modalDescription('Tiêu đề, tóm tắt, nội dung và nguồn tham khảo sẽ trở về bản này. Bản hiện tại vẫn còn trong lịch sử.')
                    ->action(function (ArticleRevision $record) {
                        /** @var Article $article */
                        $article = $this->getOwnerRecord();
                        $article->fill($record->only(Article::REVISIONED))->save();

                        Notification::make()->success()->title('Đã khôi phục bản lưu.')->send();

                        $this->redirect(ArticleResource::getUrl('edit', ['record' => $article]));
                    }),
            ]);
    }
}
