<?php

use App\Models\User;
use App\Modules\Compare\Actions\CompareSavingsRates;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Compare\Support\SavingsFilters;
use App\Modules\Core\Enums\Role;
use App\Modules\DataSync\Actions\ApproveDraft;
use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Enums\SourceHealth;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\Pages\ViewSyncDraft;
use App\Modules\DataSync\Filament\Resources\SyncSources\Pages\ListSyncSources;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Notifications\SourceAlert;
use App\Modules\DataSync\Support\SourceRegistry;
use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

const SOURCES_URL = 'http://ezfin.test/quan-tri/nguon-du-lieu';
const DRAFTS_URL = 'http://ezfin.test/quan-tri/cho-duyet';

beforeEach(function () {
    Carbon::setTestNow('2026-11-15 06:20:00');
    config(['ezfin.data_sync.delay_ms' => 0]);
    Filament::setCurrentPanel('admin');
    Feature::activate('data_sync');
    fakeBankSites();
    app(SourceRegistry::class)->sync();
});

afterEach(fn () => Carbon::setTestNow());

function source(string $key): SyncSource
{
    return SyncSource::query()->where('key', $key)->sole();
}

it('registers every bank source and runs them all from the scheduled command', function () {
    $admin = User::factory()->admin(Role::DataOperator)->create();

    $this->artisan('ezfin:sync', ['--kind' => 'savings'])->assertSuccessful();

    $savings = SyncSource::where('kind', 'savings')->get();
    expect(SyncSource::count())->toBe(count(SourceRegistry::SCRAPERS))
        ->and($savings)->toHaveCount(7)
        ->and(SyncDraft::where('status', DraftStatus::Pending)->count())->toBe(7)
        ->and($savings->every(fn (SyncSource $source) => $source->health() === SourceHealth::Ok && ! $source->auto_approve))->toBeTrue()
        ->and($admin->notifications()->count())->toBe(7);

    // Chưa duyệt thì chưa có lãi suất nào trên website.
    expect(SavingsRate::count())->toBe(0);

    // Chạy lại, số liệu không đổi: không tạo bản nháp trùng.
    $this->artisan('ezfin:sync', ['source' => 'savings.bidv'])->assertSuccessful();
    expect(source('savings.bidv')->drafts()->count())->toBe(1);
});

it('does nothing while the data_sync flag is off', function () {
    Feature::deactivate('data_sync');

    $this->artisan('ezfin:sync')->assertSuccessful()->expectsOutputToContain('data_sync đang tắt');
    expect(SyncDraft::count())->toBe(0);
    Http::assertNothingSent();
});

it('publishes approved rates as a new publication with source and keeps history', function () {
    $reviewer = User::factory()->admin(Role::DataOperator)->create();
    $run = app(RunSource::class);

    $draft = $run->handle(source('savings.mb'))['draft'];
    $message = app(ApproveDraft::class)->handle($draft, $reviewer);

    $online = FinancialProduct::where('slug', 'mb-tiet-kiem-online')->sole();
    $counter = FinancialProduct::where('slug', 'mb-tiet-kiem-tai-quay')->sole();
    expect($message)->toContain('hiệu lực từ 21/10/2026')
        ->and($online->status)->toBe(ProductStatus::Published)
        ->and($online->source_url)->toBe('https://www.mbbank.com.vn/ho-tro/bieu-lai-suat')
        ->and($online->verified_at->toDateString())->toBe('2026-11-15')
        ->and($counter->institution->short_name)->toBe('MB')
        ->and($draft->fresh()->status)->toBe(DraftStatus::Approved)
        ->and($draft->fresh()->reviewed_by)->toBe($reviewer->id);

    $best = app(CompareSavingsRates::class)->handle(new SavingsFilters(amount: 100_000_000, term: 12))->first();
    expect($best['rate']->product->is($online))->toBeTrue()->and($best['rate']->rate)->toBe('5.8000');

    // Ngân hàng đổi biểu lãi: lần duyệt sau thành lần công bố mới, lần cũ giữ làm lịch sử.
    Carbon::setTestNow('2026-11-20 06:20:00');
    $changed = str_replace('5.80%</td><td>5.48%', '6.10%</td><td>5.48%', file_get_contents(base_path('tests/Fixtures/DataSync/mb-documents.json')));
    $changed = str_replace('hiệu lực từ ngày 21-10-2026', 'hiệu lực từ ngày 19-11-2026', $changed);
    fakeBankSites(['www.mbbank.com.vn/mb-website/v1/interest-rate/documents*' => Http::response($changed)]);

    $next = $run->handle(source('savings.mb'))['draft'];
    expect($next->id)->not->toBe($draft->id)->and($next->warnings)->toBeNull();
    app(ApproveDraft::class)->handle($next, $reviewer);

    expect(SavingsRate::where('product_id', $online->id)->distinct()->count('effective_from'))->toBe(2);
    $best = app(CompareSavingsRates::class)->handle(new SavingsFilters(amount: 100_000_000, term: 12))->first();
    expect($best['rate']->rate)->toBe('6.1000');
});

it('records a failure, alerts admins once per day by bell and email, and announces recovery', function () {
    Notification::fake();
    $admin = User::factory()->admin(Role::SuperAdmin)->create();
    $editor = User::factory()->admin(Role::Editor)->create();
    fakeBankSites(['acb.com.vn/lai-suat-tien-gui' => Http::response('<html><body>Bảo trì</body></html>')]);
    $run = app(RunSource::class);

    $result = $run->handle(source('savings.acb'));

    $acb = source('savings.acb');
    expect($result['status'])->toBe('failed')
        ->and($acb->health())->toBe(SourceHealth::Failing)
        ->and($acb->last_error)->toContain('TK Truyền Thống')
        ->and($acb->consecutive_failures)->toBe(1)
        ->and($acb->last_success_at)->toBeNull();
    Notification::assertSentTo($admin, SourceAlert::class, fn ($n) => str_contains($n->subject, 'ACB'));
    Notification::assertSentTo($admin, DatabaseNotification::class);
    Notification::assertNotSentTo($editor, SourceAlert::class);

    // Lỗi tiếp trong ngày: không gửi lại.
    $run->handle(source('savings.acb'));
    Notification::assertSentToTimes($admin, SourceAlert::class, 1);
    expect(source('savings.acb')->consecutive_failures)->toBe(2);

    // Sau 24 giờ vẫn lỗi: nhắc lại.
    Carbon::setTestNow('2026-11-16 07:00:00');
    $run->handle(source('savings.acb'));
    Notification::assertSentToTimes($admin, SourceAlert::class, 2);

    // Nguồn chạy lại được: báo đã ổn.
    fakeBankSites();
    expect($run->handle(source('savings.acb'))['status'])->toBe('new');
    Notification::assertSentToTimes($admin, SourceAlert::class, 3);
    expect(source('savings.acb')->health())->toBe(SourceHealth::Ok);
});

it('fails on blocked access, robots.txt bans and implausible numbers', function (string $pattern, string $body, int $status, string $error) {
    Notification::fake();
    fakeBankSites([$pattern => Http::response($body, $status)]);

    $result = app(RunSource::class)->handle(source('savings.vietinbank'));

    expect($result['status'])->toBe('failed')->and(source('savings.vietinbank')->last_error)->toContain($error)
        ->and(SyncDraft::count())->toBe(0);
})->with([
    'blocked' => ['www.vietinbank.vn/ca-nhan/*', 'Forbidden', 403, 'từ chối truy cập (mã 403)'],
    'robots' => ['www.vietinbank.vn/robots.txt', "User-agent: *\nDisallow: /ca-nhan/\n", 200, 'robots.txt'],
    'implausible' => ['www.vietinbank.vn/ca-nhan/*', '<table><tr><th>Kỳ hạn</th><th>VND</th></tr><tr><td>6 tháng</td><td>35</td></tr><tr><td>12 tháng</td><td>54</td></tr><tr><td>1 tháng</td><td>21</td></tr><tr><td>3 tháng</td><td>24</td></tr></table>', 200, 'ngoài khoảng hợp lý'],
    'empty' => ['www.vietinbank.vn/ca-nhan/*', '<table><tr><th>Kỳ hạn</th><th>VND</th></tr></table>', 200, 'không tìm thấy dòng lãi suất'],
]);

it('flags big jumps against the last approved table and never auto-approves them', function () {
    $reviewer = User::factory()->admin(Role::DataOperator)->create();
    $source = source('savings.bidv');
    $source->update(['auto_approve' => true]);
    $run = app(RunSource::class);

    // Lần đầu hợp lệ, không cảnh báo: tự duyệt.
    expect($run->handle($source)['status'])->toBe('approved')
        ->and(FinancialProduct::where('slug', 'bidv-tiet-kiem-tai-quay')->exists())->toBeTrue();

    $jump = str_replace('"title_vi": "12 Tháng", "JPY": "", "EUR": "", "USD": "", "title_en": "12 Tháng", "VND": "5.4"', '"title_vi": "12 Tháng", "JPY": "", "EUR": "", "USD": "", "title_en": "12 Tháng", "VND": "9.9"', file_get_contents(base_path('tests/Fixtures/DataSync/bidv.json')));
    fakeBankSites(['bidv.com.vn/ServicesBIDV/InterestDetailServlet' => Http::response($jump)]);

    $result = $run->handle($source->fresh());
    expect($result['status'])->toBe('new')
        ->and($result['draft']->warnings[0])->toContain('12 tháng')->toContain('5.4000%')->toContain('9.9000%')
        ->and($result['draft']->status)->toBe(DraftStatus::Pending);
});

it('lets data operators review drafts in the admin and run a source; editors only look', function () {
    $operator = User::factory()->admin(Role::DataOperator)->create();
    $editor = User::factory()->admin(Role::Editor)->create();
    $draft = app(RunSource::class)->handle(source('savings.vietcombank'))['draft'];

    $this->actingAs($editor)->get(SOURCES_URL)->assertOk()->assertSee('Vietcombank');
    $this->get(DRAFTS_URL.'/'.$draft->id)->assertOk()->assertSee('12 tháng')->assertSee('5,4%');
    Livewire::test(ViewSyncDraft::class, ['record' => $draft->id])->assertActionHidden('approve');
    Livewire::test(ListSyncSources::class)->assertTableActionHidden('run', source('savings.vietcombank'));

    $this->actingAs(User::factory()->admin(Role::Finance)->create())->get(SOURCES_URL)->assertForbidden();

    $this->actingAs($operator);
    Livewire::test(ListSyncSources::class)->callTableAction('run', source('savings.agribank'))->assertNotified();
    expect(source('savings.agribank')->pendingDrafts()->count())->toBe(1);

    Livewire::test(ViewSyncDraft::class, ['record' => $draft->id])->callAction('approve')->assertRedirect();
    expect($draft->fresh()->status)->toBe(DraftStatus::Approved)
        ->and(FinancialInstitution::where('slug', 'vietcombank')->exists())->toBeTrue()
        ->and(SavingsRate::whereHas('product', fn ($q) => $q->where('slug', 'vietcombank-tiet-kiem-online'))->where('term_months', 12)->value('rate'))->toBe('5.4000');

    $other = source('savings.agribank')->pendingDrafts()->sole();
    Livewire::test(ViewSyncDraft::class, ['record' => $other->id])->callAction('reject', ['note' => 'Sai bảng'])->assertRedirect();
    expect($other->fresh()->status)->toBe(DraftStatus::Rejected)->and($other->fresh()->review_note)->toBe('Sai bảng');

    // Đã từ chối, lần chạy sau ra đúng số liệu đó thì không tạo lại bản nháp.
    app(RunSource::class)->handle(source('savings.agribank'));
    expect(source('savings.agribank')->pendingDrafts()->count())->toBe(0);
});
