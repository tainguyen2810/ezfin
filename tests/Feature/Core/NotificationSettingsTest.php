<?php

use App\Models\User;
use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Actions\SendAdminNotification;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\NotificationLog;
use App\Modules\Core\Models\NotificationSetting;
use App\Modules\Core\Models\NotificationTemplate;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\TemplatedMail;
use App\Modules\Core\Notifications\TemplateRenderer;
use App\Modules\DataSync\Support\AlertTypes;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const FAILED_VARS = ['ten_nguon' => 'Lãi suất ACB', 'loai_du_lieu' => 'Lãi suất tiết kiệm', 'loi' => 'Bảng <b>đổi</b> *mẫu*', 'so_lan_loi' => 2];

it('keeps the built-in wording when nothing has been customised', function () {
    $center = app(NotificationCenter::class);

    $mail = $center->render(AlertTypes::FAILED, Channel::Mail, FAILED_VARS);

    expect($mail->subject)->toBe('[EzFin] Không lấy được dữ liệu: Lãi suất ACB (Lãi suất tiết kiệm)')
        ->and($mail->greeting)->toBe('Cảnh báo dữ liệu tự động')
        ->and($mail->actionLabel)->toBe('Mở trang Nguồn dữ liệu')
        ->and($center->channels(AlertTypes::FAILED))->toBe([Channel::Database, Channel::Mail])
        ->and($center->channels(AlertTypes::DRAFT_WAITING))->toBe([Channel::Database])
        ->and($center->recipientRoles(AlertTypes::FAILED))->toBe([Role::SuperAdmin->value, Role::DataOperator->value])
        ->and($center->repeatHours(AlertTypes::FAILED))->toBe(24)
        ->and($center->repeatHours(AlertTypes::RECOVERED))->toBeNull();
});

it('escapes variables so an error message cannot inject HTML or Markdown', function () {
    $center = app(NotificationCenter::class);

    $html = (string) $center->render(AlertTypes::FAILED, Channel::Mail, FAILED_VARS)->bodyHtml();
    $bell = $center->render(AlertTypes::FAILED, Channel::Database, FAILED_VARS);

    expect($html)->toContain('&lt;b&gt;đổi&lt;/b&gt;')->toContain('*mẫu*')->not->toContain('<b>')->not->toContain('<em>')
        ->and($bell->body)->toContain('&lt;b&gt;đổi&lt;/b&gt;');
});

it('uses an edited template field by field and falls back when it renders empty', function () {
    NotificationTemplate::create(['key' => AlertTypes::FAILED, 'channel' => 'mail', 'subject' => 'Lỗi nguồn {ten_nguon}', 'body' => "**Cần xem ngay**\n\n- Lỗi: {loi}\n- Biến lạ: {khong_co}"]);

    $message = app(NotificationCenter::class)->render(AlertTypes::FAILED, Channel::Mail, FAILED_VARS);

    expect($message->subject)->toBe('Lỗi nguồn Lãi suất ACB')
        ->and($message->greeting)->toBe('Cảnh báo dữ liệu tự động')
        ->and((string) $message->bodyHtml())->toContain('<strong>Cần xem ngay</strong>')->toContain('<li>')->toContain('{khong_co}');

    NotificationTemplate::where('key', AlertTypes::FAILED)->update(['subject' => '{ten_nguon}']);
    $empty = app(NotificationCenter::class)->render(AlertTypes::FAILED, Channel::Mail, ['ten_nguon' => ''] + FAILED_VARS);
    expect($empty->subject)->toBe('[EzFin] Không lấy được dữ liệu: (Lãi suất tiết kiệm)');
});

it('lists unknown placeholders for validation', function () {
    expect(TemplateRenderer::unknown('Xin chào {ten_nguon}, {loi} và {sai_ten}', ['ten_nguon', 'loi']))->toBe(['sai_ten']);
});

it('sends to the configured roles, extra emails and channels, and logs without personal data', function () {
    Notification::fake();
    $admin = User::factory()->admin(Role::SuperAdmin)->create();
    $operator = User::factory()->admin(Role::DataOperator)->create();
    $editor = User::factory()->admin(Role::Editor)->create();

    NotificationSetting::create(['key' => AlertTypes::FAILED, 'channels' => ['mail'], 'recipient_roles' => [Role::Editor->value], 'extra_emails' => ['ops@example.com', 'khong-hop-le']]);

    expect(app(SendAdminNotification::class)->handle(AlertTypes::FAILED, FAILED_VARS, 'https://ezfin.test/quan-tri'))->toBeTrue();

    Notification::assertSentTo($editor, TemplatedMail::class);
    Notification::assertNotSentTo($admin, TemplatedMail::class);
    Notification::assertNotSentTo($operator, TemplatedMail::class);
    Notification::assertNotSentTo($editor, DatabaseNotification::class);
    Notification::assertSentTo(new AnonymousNotifiable, TemplatedMail::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === ['ops@example.com']);

    $log = NotificationLog::sole();
    expect($log->only(['key', 'channel', 'recipients', 'status']))->toBe(['key' => AlertTypes::FAILED, 'channel' => 'mail', 'recipients' => 2, 'status' => 'sent'])
        ->and(json_encode($log->toArray()))->not->toContain('example.com');
});

it('sends nothing when the type is switched off', function () {
    Notification::fake();
    User::factory()->admin(Role::SuperAdmin)->create();
    NotificationSetting::create(['key' => AlertTypes::FAILED, 'enabled' => false]);

    expect(app(SendAdminNotification::class)->handle(AlertTypes::FAILED, FAILED_VARS))->toBeFalse();
    Notification::assertNothingSent();
    expect(NotificationLog::count())->toBe(0);
});

it('logs a failed email without breaking the caller', function () {
    $admin = User::factory()->admin(Role::SuperAdmin)->create();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);

    expect(app(SendAdminNotification::class)->handle(AlertTypes::FAILED, FAILED_VARS))->toBeTrue();

    expect(NotificationLog::where('channel', 'mail')->value('status'))->toBe('failed')
        ->and(NotificationLog::where('channel', 'database')->value('status'))->toBe('sent')
        ->and($admin->notifications()->count())->toBe(1);
});

it('prunes delivery logs after the retention period', function () {
    NotificationLog::create(['key' => 'x', 'channel' => 'mail', 'status' => 'sent'])->forceFill(['created_at' => now()->subDays(91)])->save();
    NotificationLog::create(['key' => 'y', 'channel' => 'mail', 'status' => 'sent']);

    $this->artisan('model:prune', ['--model' => [NotificationLog::class]])->assertSuccessful();

    expect(NotificationLog::pluck('key')->all())->toBe(['y']);
});
