<?php

use App\Models\User;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\ProcessRecurringTransactions;
use App\Modules\Personal\Enums\RecurringFrequency;
use App\Modules\Personal\Mail\DueReminderMail;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\RecurringTransaction;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const RECURRING = 'http://app.ezfin.test/dinh-ky';

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-31 09:00'));
    Feature::activate('personal');
    $this->user = User::factory()->create(['email' => 'a@example.com']);
    app(CreateDefaultCategories::class)->handle($this->user);
    $this->actingAs($this->user);
    $this->bank = Wallet::factory()->for($this->user)->create(['name' => 'Vietcombank', 'opening_balance' => 10_000_000]);
    $this->rent = Category::where('name', 'Nhà ở')->sole();
    $this->salary = Category::where('name', 'Lương')->sole();
});

/** Tạo khoản định kỳ cho người đang đăng nhập; kỳ đến hạn tính từ $from (mặc định hôm nay). */
function recurring(array $attributes = [], ?string $from = null): RecurringTransaction
{
    $item = new RecurringTransaction([
        'name' => 'Tiền nhà', 'type' => 'expense', 'amount' => 6_000_000, 'wallet_id' => test()->bank->id,
        'category_id' => test()->rent->id, 'frequency' => 'monthly', 'starts_on' => '2026-10-31', 'mode' => 'auto', ...$attributes,
    ]);
    $item->scheduleFrom(Carbon::parse($from ?? today()));
    $item->save();

    return $item;
}

it('keeps the anchor day when months are shorter', function () {
    $start = Carbon::parse('2026-01-31');

    expect(collect(range(0, 3))->map(fn ($n) => RecurringFrequency::Monthly->occurrence($start, $n)->toDateString())->all())
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'])
        ->and(RecurringFrequency::Quarterly->occurrence($start, 1)->toDateString())->toBe('2026-04-30')
        ->and(RecurringFrequency::Yearly->occurrence(Carbon::parse('2024-02-29'), 1)->toDateString())->toBe('2025-02-28')
        ->and(RecurringFrequency::Weekly->occurrence($start, 2)->toDateString())->toBe('2026-02-14');

    $item = recurring(['starts_on' => '2026-01-31', 'ends_on' => '2026-12-15'], '2026-03-01');
    expect($item->next_due_on->toDateString())->toBe('2026-03-31')
        ->and($item->occurrenceOnOrAfter(Carbon::parse('2026-12-01')))->toBeNull();
});

it('records due auto items, catches up missed periods and never duplicates on rerun', function () {
    recurring(['starts_on' => '2026-08-05', 'name' => 'Lương', 'type' => 'income', 'category_id' => $this->salary->id, 'amount' => 25_000_000], '2026-08-01');
    recurring(['starts_on' => '2026-10-31', 'mode' => 'confirm', 'name' => 'Điện']);

    $this->artisan('ezfin:recurring')->expectsOutputToContain('đã ghi 3 giao dịch')->assertSuccessful();
    $this->artisan('ezfin:recurring')->expectsOutputToContain('đã ghi 0 giao dịch')->assertSuccessful();

    $salary = RecurringTransaction::firstWhere('name', 'Lương');
    expect(Transaction::where('recurring_transaction_id', $salary->id)->orderBy('occurred_on')->pluck('occurred_on')->map->toDateString()->all())
        ->toBe(['2026-08-05', '2026-09-05', '2026-10-05'])
        ->and($salary->next_due_on->toDateString())->toBe('2026-11-05')
        ->and($salary->last_occurrence_on->toDateString())->toBe('2026-10-05')
        ->and(Transaction::where('note', 'Điện')->exists())->toBeFalse()
        ->and($this->bank->fresh()->balance())->toBe(10_000_000 + 75_000_000);
});

it('creates the transaction right away when a new auto item is due today', function () {
    $this->post(RECURRING, ['type' => 'expense', 'name' => 'Tiền nhà', 'amount' => '6.000.000', 'wallet_id' => $this->bank->id,
        'category_id' => $this->rent->id, 'frequency' => 'monthly', 'starts_on' => '2026-10-31', 'mode' => 'auto', 'remind_days_before' => '3'])
        ->assertRedirect(RECURRING);

    $item = RecurringTransaction::sole();
    expect($item->user_id)->toBe($this->user->id)
        ->and($item->remind_days_before)->toBe(3)
        ->and($item->next_due_on->toDateString())->toBe('2026-11-30')
        ->and(Transaction::where('recurring_transaction_id', $item->id)->count())->toBe(1);

    // Sửa lịch không ghi lại kỳ đã ghi hôm nay.
    $this->put(RECURRING.'/'.$item->id, ['type' => 'expense', 'name' => 'Tiền nhà mới', 'amount' => '6.500.000', 'wallet_id' => $this->bank->id,
        'category_id' => $this->rent->id, 'frequency' => 'monthly', 'starts_on' => '2026-10-31', 'mode' => 'auto', 'remind_days_before' => ''])
        ->assertRedirect(RECURRING);
    expect(Transaction::where('recurring_transaction_id', $item->id)->count())->toBe(1)
        ->and($item->fresh()->next_due_on->toDateString())->toBe('2026-11-30')
        ->and($item->fresh()->remind_days_before)->toBeNull();
});

it('lets the user confirm or skip a due bill, once', function () {
    $item = recurring(['mode' => 'confirm', 'starts_on' => '2026-10-29'], '2026-10-01');

    $this->get(RECURRING)->assertOk()->assertSee('Quá hạn 2 ngày')->assertSee('Đã trả');
    $this->post(RECURRING."/{$item->id}/ghi-nhan", ['due' => '2026-10-29'])->assertRedirect();
    $this->post(RECURRING."/{$item->id}/ghi-nhan", ['due' => '2026-10-29'])->assertSessionHas('toast.type', 'warning');

    $recorded = Transaction::where('recurring_transaction_id', $item->id)->sole();
    expect($recorded->occurred_on->toDateString())->toBe('2026-10-31')
        ->and($item->fresh()->next_due_on->toDateString())->toBe('2026-11-29');

    $this->travelTo(Carbon::parse('2026-11-29'));
    $this->post(RECURRING."/{$item->id}/bo-qua", ['due' => '2026-11-29'])->assertRedirect();
    expect($item->fresh()->next_due_on->toDateString())->toBe('2026-12-29')
        ->and(Transaction::where('recurring_transaction_id', $item->id)->count())->toBe(1);
});

it('reminds without recording for card due dates and rolls over after the due date', function () {
    $this->post(RECURRING, ['type' => 'expense', 'name' => 'Hạn trả thẻ VIB', 'amount' => '4.800.000', 'frequency' => 'monthly',
        'starts_on' => '2026-11-02', 'mode' => 'remind', 'remind_days_before' => '3'])->assertRedirect(RECURRING);
    $item = RecurringTransaction::sole();
    expect($item->wallet_id)->toBeNull();

    $this->post(RECURRING, ['type' => 'expense', 'name' => 'Không ví', 'amount' => '100.000', 'frequency' => 'monthly',
        'starts_on' => '2026-11-02', 'mode' => 'auto'])->assertSessionHasErrors(['wallet_id', 'category_id']);

    Auth::logout();
    $this->artisan('ezfin:recurring', ['--date' => '2026-11-03'])->assertSuccessful();
    expect(Transaction::withoutGlobalScopes()->count())->toBe(0)
        ->and($item->fresh()->next_due_on->toDateString())->toBe('2026-12-02');
});

it('emails each user once per due date, only inside the reminder window', function () {
    Mail::fake();
    recurring(['name' => 'Tiền nhà', 'starts_on' => '2026-11-03', 'remind_days_before' => 3]);
    recurring(['name' => 'Bảo hiểm', 'amount' => 3_600_000, 'starts_on' => '2026-11-20', 'frequency' => 'yearly', 'remind_days_before' => 7, 'mode' => 'confirm']);
    recurring(['name' => 'Không nhắc', 'starts_on' => '2026-11-01', 'remind_days_before' => null]);
    $other = User::factory()->create(['email' => 'b@example.com']);
    $theirWallet = Wallet::factory()->for($other)->create();
    $theirs = new RecurringTransaction(['name' => 'Của B', 'type' => 'expense', 'amount' => 1_000_000, 'wallet_id' => $theirWallet->id,
        'frequency' => 'monthly', 'starts_on' => '2026-11-01', 'mode' => 'remind', 'remind_days_before' => 1]);
    $theirs->user_id = $other->id;
    $theirs->scheduleFrom(today());
    $theirs->save();
    Auth::logout();

    $this->artisan('ezfin:recurring')->expectsOutputToContain('gửi 2 email')->assertSuccessful();
    $this->artisan('ezfin:recurring')->expectsOutputToContain('gửi 0 email')->assertSuccessful();

    Mail::assertQueued(DueReminderMail::class, 2);
    Mail::assertQueued(DueReminderMail::class, fn (DueReminderMail $mail) => $mail->hasTo('a@example.com')
        && $mail->items->pluck('name')->all() === ['Tiền nhà']);
    Mail::assertQueued(DueReminderMail::class, fn (DueReminderMail $mail) => $mail->hasTo('b@example.com')
        && $mail->items->pluck('name')->all() === ['Của B']);

    // Bảo hiểm vào cửa sổ nhắc 7 ngày từ 13/11.
    $this->artisan('ezfin:recurring', ['--date' => '2026-11-13'])->expectsOutputToContain('gửi 1 email');
    $mail = new DueReminderMail($this->user, RecurringTransaction::withoutGlobalScopes()->where('name', 'Bảo hiểm')->get(), Carbon::parse('2026-11-13'));
    expect($mail->render())->toContain('20/11/2026')->toContain('3.600.000 đ')->not->toContain('6.000.000 đ');
});

it('keeps another user\'s recurring items out of reach', function () {
    $other = User::factory()->create();
    $theirWallet = Wallet::factory()->for($other)->create();
    $theirs = new RecurringTransaction(['name' => 'Bí mật của B', 'type' => 'expense', 'amount' => 1_000_000, 'wallet_id' => $theirWallet->id,
        'frequency' => 'monthly', 'starts_on' => '2026-10-30', 'mode' => 'confirm']);
    $theirs->user_id = $other->id;
    $theirs->scheduleFrom(Carbon::parse('2026-10-01'));
    $theirs->save();

    $this->get(RECURRING)->assertOk()->assertDontSee('Bí mật của B');
    $this->get(RECURRING."/{$theirs->id}/edit")->assertNotFound();
    $this->put(RECURRING."/{$theirs->id}", ['name' => 'x'])->assertNotFound();
    $this->post(RECURRING."/{$theirs->id}/ghi-nhan")->assertNotFound();
    $this->post(RECURRING."/{$theirs->id}/bo-qua")->assertNotFound();
    $this->delete(RECURRING."/{$theirs->id}")->assertNotFound();

    $this->post(RECURRING, ['type' => 'expense', 'name' => 'Dùng ví B', 'amount' => '100.000', 'wallet_id' => $theirWallet->id,
        'category_id' => $this->rent->id, 'frequency' => 'monthly', 'starts_on' => '2026-11-01', 'mode' => 'auto'])
        ->assertSessionHasErrors('wallet_id');
    expect($theirs->fresh()->next_due_on->toDateString())->toBe('2026-10-30');
});

it('keeps recorded transactions when the recurring item is deleted', function () {
    $item = recurring();
    app(ProcessRecurringTransactions::class)->catchUp($item, today());

    $this->delete(RECURRING."/{$item->id}")->assertRedirect(RECURRING);
    expect(RecurringTransaction::count())->toBe(0)
        ->and(Transaction::count())->toBe(1)
        ->and(Transaction::sole()->recurring_transaction_id)->toBeNull();
});

it('shows upcoming items on the dashboard', function () {
    recurring(['name' => 'Hạn thẻ', 'mode' => 'remind', 'starts_on' => '2026-11-03', 'wallet_id' => null, 'category_id' => null]);
    recurring(['name' => 'Xa lắm', 'starts_on' => '2026-12-20']);

    $this->get('http://app.ezfin.test/tong-quan')->assertOk()->assertSee('Sắp đến hạn')->assertSee('Hạn thẻ')->assertDontSee('Xa lắm');
});

it('does nothing when the personal app is switched off', function () {
    recurring(['starts_on' => '2026-10-01'], '2026-10-01');
    Feature::deactivate('personal');

    $this->artisan('ezfin:recurring')->expectsOutputToContain('đang tắt')->assertSuccessful();
    expect(Transaction::count())->toBe(0);
    $this->get(RECURRING)->assertNotFound();
});
