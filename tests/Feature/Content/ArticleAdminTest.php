<?php

use App\Models\User;
use App\Modules\Content\Actions\ArticleWorkflow;
use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Filament\Resources\ArticleCategories\Pages\EditArticleCategory;
use App\Modules\Content\Filament\Resources\Articles\Pages\CreateArticle;
use App\Modules\Content\Filament\Resources\Articles\Pages\EditArticle;
use App\Modules\Content\Filament\Resources\Articles\Pages\ViewArticle;
use App\Modules\Content\Filament\Resources\Articles\RelationManagers\RevisionsRelationManager;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\ArticleRevision;
use App\Modules\Content\Models\Author;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const CONTENT_ADMIN_URL = 'http://app.ezfin.test/quan-tri/';

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function articleForm(array $overrides = []): array
{
    return [
        'title' => 'Lãi kép là gì',
        'slug' => 'lai-kep-la-gi',
        'category_id' => ArticleCategory::factory()->create()->id,
        'author_id' => Author::factory()->create()->id,
        'excerpt' => 'Giải thích lãi kép và cách tính.',
        'body' => '<h2>Định nghĩa</h2><p>Lãi kép là lãi được nhập vào gốc.</p>',
        'references' => [['title' => 'Thông tư 48/2018/TT-NHNN', 'url' => 'https://sbv.gov.vn']],
        ...$overrides,
    ];
}

function contentEditor(): User
{
    return User::factory()->admin(Role::Editor)->create();
}

function contentReviewer(): User
{
    return User::factory()->admin(Role::SuperAdmin)->create();
}

it('lets editors and super admins manage content, other roles not', function (Role $role, int $status) {
    $this->actingAs(User::factory()->admin($role)->create());

    foreach (['bai-viet', 'bai-viet/create', 'chuyen-muc', 'chuyen-muc/create', 'tac-gia', 'tac-gia/create'] as $path) {
        $this->get(CONTENT_ADMIN_URL.$path)->assertStatus($status);
    }
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'editor' => [Role::Editor, 200],
    'data operator' => [Role::DataOperator, 403],
    'finance' => [Role::Finance, 403],
]);

it('saves an editor draft, records who wrote it and keeps a first revision', function () {
    $editor = contentEditor();
    $this->actingAs($editor);

    Livewire::test(CreateArticle::class)
        ->fillForm(articleForm())
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::sole();

    expect($article->status)->toBe(ArticleStatus::Draft)
        ->and($article->created_by)->toBe($editor->id)
        ->and($article->published_at)->toBeNull()
        ->and($article->references)->toBe([['title' => 'Thông tư 48/2018/TT-NHNN', 'url' => 'https://sbv.gov.vn']])
        ->and($article->revisions()->count())->toBe(1)
        ->and($article->revisions()->first()->user_id)->toBe($editor->id);
});

it('strips XSS from the body whichever way it is saved', function () {
    $this->actingAs(contentEditor());

    Livewire::test(CreateArticle::class)
        ->fillForm(articleForm(['body' => '<p>Nội dung</p><p><a href="javascript:alert(1)">bấm</a></p>']))
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::sole();
    $article->update(['body' => '<p>Sửa</p><script>alert(1)</script><img src="/storage/a.png" onerror="alert(1)"><iframe src="https://x.test"></iframe>']);

    expect($article->body)->not->toContain('javascript:')
        ->not->toContain('<script')->not->toContain('onerror')->not->toContain('<iframe')
        ->toContain('<p>Sửa</p>')
        ->and($article->revisions()->first()->body)->not->toContain('<script');
});

it('refuses reference and profile links that are not http or https', function () {
    $this->actingAs(contentEditor());

    Livewire::test(CreateArticle::class)
        ->fillForm(articleForm(['references' => [['title' => 'Nguồn', 'url' => 'javascript:alert(1)']]]))
        ->call('create')
        ->assertHasFormErrors();

    $article = Article::factory()->create(['references' => [['title' => 'Nguồn', 'url' => 'javascript:alert(1)'], ['title' => '', 'url' => 'https://a.vn']]]);
    $author = Author::factory()->create(['links' => ['javascript:alert(1)', 'https://linkedin.com/in/a']]);

    expect($article->references)->toBe([['title' => 'Nguồn', 'url' => null]])
        ->and($author->links)->toBe(['https://linkedin.com/in/a']);
});

it('lets an editor submit a draft but not publish it', function () {
    $article = Article::factory()->create();
    $this->actingAs(contentEditor());

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionVisible('submit')
        ->assertActionHidden('publish')
        ->assertActionHidden('unpublish')
        ->callAction('submit');

    expect($article->refresh()->status)->toBe(ArticleStatus::InReview)
        ->and($article->submitted_at)->not->toBeNull();
});

it('does not submit an article without a summary or content', function () {
    $article = Article::factory()->create(['excerpt' => null, 'body' => '<p></p>']);
    $this->actingAs(contentEditor());

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('submit')
        ->assertNotified('Chưa thực hiện được');

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft);
});

it('blocks editors from publishing even outside the admin buttons', function () {
    $draft = Article::factory()->create();
    $published = Article::factory()->published()->create();
    $editor = contentEditor();
    $this->actingAs($editor);

    expect(fn () => app(ArticleWorkflow::class)->publish($editor, $draft))->toThrow(AuthorizationException::class)
        ->and(fn () => $draft->forceFill(['status' => ArticleStatus::Published])->save())->toThrow(AuthorizationException::class)
        ->and(fn () => $published->update(['body' => '<p>Sửa lén</p>']))->toThrow(AuthorizationException::class)
        ->and(fn () => app(ArticleWorkflow::class)->unpublish($editor, $published))->toThrow(AuthorizationException::class);

    expect($draft->refresh()->status)->toBe(ArticleStatus::Draft)
        ->and($published->refresh()->body)->toBe('<p>Nội dung bài viết thử nghiệm.</p>');
});

it('locks articles in review or published for editors, who can still view them', function (string $state) {
    $article = Article::factory()->{$state}()->create();
    $this->actingAs(contentEditor());

    $this->get(CONTENT_ADMIN_URL.'bai-viet/'.$article->id.'/edit')->assertForbidden();
    $this->get(CONTENT_ADMIN_URL.'bai-viet/'.$article->id)->assertOk()->assertSee($article->title);
})->with(['inReview', 'published']);

it('lets an editor withdraw a submitted article back to draft', function () {
    $article = Article::factory()->inReview()->create();
    $this->actingAs(contentEditor());

    Livewire::test(ViewArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionHidden('publish')
        ->callAction('withdraw');

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft);
});

it('lets the reviewer publish now or schedule, and records who approved', function () {
    $now = Article::factory()->inReview()->create();
    $later = Article::factory()->inReview()->create();
    $reviewer = contentReviewer();
    $this->actingAs($reviewer);

    Livewire::test(EditArticle::class, ['record' => $now->getRouteKey()])
        ->callAction('publish', ['published_at' => now()->toDateTimeString()])
        ->assertHasNoActionErrors();
    Livewire::test(EditArticle::class, ['record' => $later->getRouteKey()])
        ->callAction('publish', ['published_at' => now()->addDays(2)->toDateTimeString()]);

    expect($now->refresh()->status)->toBe(ArticleStatus::Published)
        ->and($now->reviewed_by)->toBe($reviewer->id)
        ->and($now->isPublic())->toBeTrue()
        ->and($later->refresh()->status)->toBe(ArticleStatus::Published)
        ->and($later->isPublic())->toBeFalse()
        ->and(Article::published()->pluck('id')->all())->toBe([$now->id]);
});

it('returns an article to the editor with a note', function () {
    $article = Article::factory()->inReview()->create();
    $this->actingAs(contentReviewer());

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('return', ['note' => 'Bổ sung nguồn cho số liệu lãi suất.']);

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft)
        ->and($article->review_note)->toBe('Bổ sung nguồn cho số liệu lãi suất.');

    $this->actingAs(contentEditor());
    $this->get(CONTENT_ADMIN_URL.'bai-viet/'.$article->id.'/edit')->assertOk()->assertSee('Bổ sung nguồn cho số liệu lãi suất.');
});

it('lets the reviewer edit a live article and take it down', function () {
    $article = Article::factory()->published()->create();
    $this->actingAs(contentReviewer());

    $article->update(['body' => '<p>Sửa số liệu.</p>']);
    expect($article->refresh()->body)->toBe('<p>Sửa số liệu.</p>');

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('unpublish');

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft)
        ->and($article->isPublic())->toBeFalse();
});

it('keeps a revision for each content change and restores an old one', function () {
    $article = Article::factory()->create(['body' => '<p>Bản một</p>']);
    $this->actingAs(contentEditor());

    $article->update(['body' => '<p>Bản hai</p>']);
    $article->forceFill(['status' => ArticleStatus::InReview])->save();
    $article->forceFill(['status' => ArticleStatus::Draft])->save();

    expect($article->revisions()->count())->toBe(2);

    $first = $article->revisions()->reorder('id')->first();

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class])
        ->assertCanSeeTableRecords($article->revisions)
        ->assertTableActionVisible('restore', $first)
        ->callTableAction('restore', $first)
        ->assertNotified('Đã khôi phục bản lưu.');

    expect($article->refresh()->body)->toBe('<p>Bản một</p>')
        ->and($article->revisions()->count())->toBe(3)
        ->and(fn () => $first->update(['body' => 'x']))->toThrow(LogicException::class);
});

it('hides revision restore from editors once the article is locked', function () {
    $article = Article::factory()->inReview()->create();
    $this->actingAs(contentEditor());

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => ViewArticle::class])
        ->assertTableActionHidden('restore', $article->revisions()->first());
});

it('writes article changes to the audit log without copying the body', function () {
    $this->actingAs(contentEditor());
    $article = Article::factory()->create();
    $article->update(['title' => 'Tiêu đề mới', 'body' => '<p>Nội dung mới</p>']);

    $log = AuditLog::where('auditable_type', $article->getMorphClass())->where('event', 'updated')->sole();

    expect($log->new_values)->toHaveKey('title', 'Tiêu đề mới')
        ->and($log->new_values)->not->toHaveKey('body');
});

it('requires a summary and content before publishing', function () {
    $article = Article::factory()->create(['excerpt' => null]);
    $this->actingAs(contentReviewer());

    expect(fn () => app(ArticleWorkflow::class)->publish(auth()->user(), $article))->toThrow(ValidationException::class);
});

it('soft deletes articles and only super admins can delete', function () {
    $article = Article::factory()->create();

    $this->actingAs(contentEditor());
    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])->assertActionHidden(DeleteAction::class);

    $this->actingAs(contentReviewer());
    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])->callAction(DeleteAction::class);

    expect(Article::count())->toBe(0)
        ->and(Article::withTrashed()->count())->toBe(1)
        ->and(ArticleRevision::count())->toBe(1);
});

it('does not delete a category or author that still has articles', function () {
    $article = Article::factory()->create();
    $article->delete();
    $this->actingAs(contentReviewer());

    Livewire::test(EditArticleCategory::class, ['record' => $article->category->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    expect(auth()->user()->can('delete', $article->author))->toBeFalse()
        ->and(auth()->user()->can('delete', Author::factory()->create()))->toBeTrue();
});
