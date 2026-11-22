<?php

use App\Models\User;
use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Filament\Resources\NotificationSettings\Pages\EditNotificationSetting;
use App\Modules\Core\Filament\Resources\NotificationSettings\Pages\ListNotificationSettings;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\NotificationLog;
use App\Modules\Core\Models\NotificationSetting;
use App\Modules\Core\Models\NotificationTemplate;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\TemplatedMail;
use App\Modules\DataSync\Support\AlertTypes;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const NOTIFY_URL = 'http://ezfin.test/quan-tri/thong-bao';

function failedSetting(): NotificationSetting
{
    NotificationSetting::syncCatalog();

    return NotificationSetting::where('key', AlertTypes::FAILED)->sole();
}

it('lists every registered type to super admins only, without audit noise', function () {
    $admin = User::factory()->admin(Role::SuperAdmin)->create();

    $this->actingAs($admin)->get(NOTIFY_URL)->assertOk()
        ->assertSee('Nguồn dữ liệu bị lỗi')->assertSee('Dữ liệu mới chờ duyệt')->assertSee('Mặc định');
    expect(AuditLog::where('auditable_type', (new NotificationSetting)->getMorphClass())->count())->toBe(0);

    $this->get(NOTIFY_URL.'/'.failedSetting()->id)->assertOk()->assertSee('{ten_nguon}')->assertSee('Xem trước');

    // Xem trước email dựng trong Livewire: tiêu đề Markdown vẫn thành <h1>, không lộ dấu #.
    Livewire::test(EditNotificationSetting::class, ['record' => failedSetting()->id])
        ->assertSeeHtml('&lt;h1')->assertDontSeeHtml('# Cảnh báo dữ liệu tự động');

    foreach ([Role::Editor, Role::DataOperator, Role::Finance] as $role) {
        $this->actingAs(User::factory()->admin($role)->create())->get(NOTIFY_URL)->assertForbidden();
    }
});

it('saves only what differs from the defaults and applies it to real alerts', function () {
    $admin = User::factory()->admin(Role::SuperAdmin)->create();
    $this->actingAs($admin);
    $record = failedSetting();

    Livewire::test(EditNotificationSetting::class, ['record' => $record->id])
        ->assertSchemaStateSet(['channels' => ['database', 'mail'], 'recipient_roles' => [Role::SuperAdmin->value, Role::DataOperator->value], 'templates.mail.greeting' => 'Cảnh báo dữ liệu tự động'])
        ->fillForm([
            'channels' => ['mail'],
            'repeat_hours' => 6,
            'extra_emails' => ['Ops@Example.com'],
            'templates.mail.subject' => 'Nguồn {ten_nguon} hỏng',
            'templates.mail.body' => '[EzFin] Không lấy được dữ liệu: {ten_nguon} ({loai_du_lieu})',
            'templates.database.subject' => 'Không lấy được dữ liệu: {ten_nguon} ({loai_du_lieu})',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Đã lưu cài đặt thông báo');

    $record->refresh();
    expect($record->channels)->toBe(['mail'])
        ->and($record->recipient_roles)->toBeNull()
        ->and($record->repeat_hours)->toBe(6)
        ->and($record->extra_emails)->toBe(['ops@example.com'])
        ->and(NotificationTemplate::where('channel', 'database')->exists())->toBeFalse();

    $mail = NotificationTemplate::where('channel', 'mail')->sole();
    expect($mail->subject)->toBe('Nguồn {ten_nguon} hỏng')->and($mail->greeting)->toBeNull()->and($mail->updated_by)->toBe($admin->id);

    $center = app(NotificationCenter::class);
    expect($center->channels(AlertTypes::FAILED))->toBe([Channel::Mail])
        ->and($center->repeatHours(AlertTypes::FAILED))->toBe(6)
        ->and($center->render(AlertTypes::FAILED, Channel::Mail, ['ten_nguon' => 'ACB'])->subject)->toBe('Nguồn ACB hỏng');
});

it('rejects unknown placeholders and invalid emails', function () {
    $this->actingAs(User::factory()->admin(Role::SuperAdmin)->create());

    Livewire::test(EditNotificationSetting::class, ['record' => failedSetting()->id])
        ->fillForm(['templates.mail.subject' => 'Lỗi {ten_nguon} {sai_bien}', 'extra_emails' => ['khong-phai-email']])
        ->call('save')
        ->assertHasFormErrors(['templates.mail.subject', 'extra_emails.0']);

    expect(NotificationTemplate::count())->toBe(0);
});

it('can switch a type off and restore every default', function () {
    $this->actingAs(User::factory()->admin(Role::SuperAdmin)->create());
    $record = failedSetting();

    Livewire::test(EditNotificationSetting::class, ['record' => $record->id])
        ->fillForm(['enabled' => false, 'templates.database.body' => 'Lỗi: {loi}'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect(app(NotificationCenter::class)->channels(AlertTypes::FAILED))->toBe([]);

    Livewire::test(ListNotificationSettings::class)->assertCanSeeTableRecords([$record])->assertSee('Đang tắt');

    Livewire::test(EditNotificationSetting::class, ['record' => $record->id])
        ->callAction('restoreDefaults')
        ->assertSchemaStateSet(['enabled' => true, 'repeat_hours' => 24, 'templates.database.body' => '{loi} (lỗi liên tiếp {so_lan_loi} lần)']);

    expect(NotificationTemplate::count())->toBe(0)
        ->and($record->fresh()->only(['enabled', 'channels', 'repeat_hours']))->toBe(['enabled' => true, 'channels' => null, 'repeat_hours' => null]);
});

it('sends the unsaved draft as a test to the current admin only', function () {
    Notification::fake();
    $admin = User::factory()->admin(Role::SuperAdmin)->create();
    $other = User::factory()->admin(Role::DataOperator)->create();
    $this->actingAs($admin);

    Livewire::test(EditNotificationSetting::class, ['record' => failedSetting()->id])
        ->fillForm(['templates.mail.subject' => 'Thử {ten_nguon}'])
        ->callAction('sendTest')
        ->assertNotified('Đã gửi thử');

    Notification::assertSentTo($admin, TemplatedMail::class, fn ($n) => $n->message->subject === 'Thử Lãi suất tiết kiệm ACB');
    Notification::assertSentTo($admin, DatabaseNotification::class);
    Notification::assertNothingSentTo($other);
    expect(NotificationTemplate::count())->toBe(0)
        ->and(NotificationLog::where('test', true)->count())->toBe(2);
});

it('explains a failed test email instead of crashing', function () {
    $this->actingAs(User::factory()->admin(Role::SuperAdmin)->create());
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);

    Livewire::test(EditNotificationSetting::class, ['record' => failedSetting()->id])
        ->fillForm(['channels' => ['mail']])
        ->callAction('sendTest')
        ->assertNotified('Không gửi được email');

    expect(NotificationLog::sole()->status)->toBe('failed');
});

it('shows customer and subscriber emails with their fixed parts, and keeps mandatory ones on', function () {
    $this->actingAs(User::factory()->admin(Role::SuperAdmin)->create());
    NotificationSetting::syncCatalog();
    $confirm = NotificationSetting::where('key', 'newsletter.confirm')->sole();

    $this->get(NOTIFY_URL)->assertOk()->assertSee('Xác nhận đăng ký nhận tin')->assertSee('Nhắc khoản định kỳ sắp đến hạn')->assertSee('Người nhận tin qua email');

    Livewire::test(EditNotificationSetting::class, ['record' => $confirm->id])
        ->assertSee('Phần hệ thống tự thêm, không sửa được')
        ->assertFormFieldDisabled('enabled')
        ->fillForm(['templates.mail.subject' => 'Xác nhận giúp EzFin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($confirm->fresh()->enabled)->toBeTrue()
        ->and(app(NotificationCenter::class)->render('newsletter.confirm', Channel::Mail, [])->subject)->toBe('Xác nhận giúp EzFin');
});
