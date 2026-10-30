<?php

use App\Models\User;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\GoalContribution;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const GOALS = 'http://app.ezfin.test/muc-tieu';

beforeEach(function () {
    Feature::activate('personal');
    Carbon::setTestNow('2026-10-30 09:00');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

function makeGoal(array $attributes = [], int $saved = 0): Goal
{
    $goal = Goal::create([...['name' => 'Mua nhà', 'target_amount' => 1_500_000_000, 'deadline' => '2028-12-31', 'icon' => 'home', 'color' => 'blue'], ...$attributes]);
    if ($saved) {
        $goal->contributions()->create(['amount' => $saved, 'contributed_on' => '2026-10-01']);
    }

    return $goal;
}

it('creates a goal with the amount already saved', function () {
    $this->post(GOALS, [
        'name' => 'Du lịch Nhật Bản', 'target_amount' => '120.000.000', 'initial_amount' => '72.000.000',
        'deadline' => '2027-06-30', 'icon' => 'globe-asia-australia', 'color' => 'teal',
    ])->assertRedirect();

    $goal = Goal::withSaved()->sole();
    expect($goal->target())->toBe(120_000_000)
        ->and($goal->savedAmount())->toBe(72_000_000)
        ->and($goal->user_id)->toBe($this->user->id)
        ->and($goal->deadline->toDateString())->toBe('2027-06-30');
});

it('validates the goal form', function () {
    $this->post(GOALS, ['name' => '', 'target_amount' => '0', 'icon' => 'không-có', 'color' => 'cam'])
        ->assertSessionHasErrors(['name', 'target_amount', 'icon', 'color']);

    expect(Goal::count())->toBe(0);
});

it('lists active goals with progress and the monthly amount needed', function () {
    makeGoal(saved: 480_000_000);
    makeGoal(['name' => 'Quỹ dự phòng', 'target_amount' => 60_000_000, 'deadline' => null, 'icon' => 'shield-check'], 60_000_000);

    $this->get(GOALS)->assertOk()
        ->assertSee('Mua nhà')->assertSee('480.000.000 đ')->assertSee('32%')
        ->assertSee('Còn 26 tháng')->assertSee('39.230.770 đ/tháng')
        ->assertSee('Đang thực hiện (1)')->assertSee('Đã hoàn thành (1)')
        ->assertDontSee('Quỹ dự phòng');

    $this->get(GOALS.'?trang_thai=hoan-thanh')->assertOk()
        ->assertSee('Quỹ dự phòng')->assertSee('Đã đạt mục tiêu')->assertDontSee('Mua nhà');
});

it('records contributions and withdrawals without touching wallet balances', function () {
    $wallet = Wallet::factory()->for($this->user)->create(['opening_balance' => 10_000_000]);
    $goal = makeGoal(['target_amount' => 20_000_000]);

    $this->post(GOALS."/{$goal->id}/gop", ['direction' => 'gop', 'amount' => '15.000.000', 'contributed_on' => '2026-10-20', 'note' => 'Thưởng'])
        ->assertRedirect(GOALS."/{$goal->id}");
    $this->post(GOALS."/{$goal->id}/gop", ['direction' => 'rut', 'amount' => '5000000', 'contributed_on' => '2026-10-25']);

    expect($goal->fresh()->savedAmount())->toBe(10_000_000)
        ->and(GoalContribution::pluck('amount')->map(fn ($a) => (int) $a)->all())->toBe([15_000_000, -5_000_000])
        ->and(Wallet::withBalance()->find($wallet->id)->balance())->toBe(10_000_000);

    $this->get(GOALS."/{$goal->id}")->assertOk()->assertSee('Thưởng')->assertSee('+15.000.000 đ')->assertSee('-5.000.000 đ');
});

it('does not withdraw more than was saved', function () {
    $goal = makeGoal(saved: 3_000_000);

    $this->post(GOALS."/{$goal->id}/gop", ['direction' => 'rut', 'amount' => '4.000.000', 'contributed_on' => '2026-10-25'])
        ->assertSessionHasErrors('amount');

    expect($goal->fresh()->savedAmount())->toBe(3_000_000);
});

it('congratulates when the goal is reached', function () {
    $goal = makeGoal(['target_amount' => 5_000_000], 4_000_000);

    $this->post(GOALS."/{$goal->id}/gop", ['direction' => 'gop', 'amount' => '1.000.000', 'contributed_on' => '2026-10-30'])
        ->assertSessionHas('toast.message', 'Chúc mừng, bạn đã đạt mục tiêu!');
});

it('updates and deletes a goal with its history', function () {
    $goal = makeGoal(saved: 1_000_000);

    $this->put(GOALS."/{$goal->id}", ['name' => 'Mua căn hộ', 'target_amount' => '2.000.000.000', 'icon' => 'home', 'color' => 'green', 'deadline' => ''])
        ->assertRedirect(GOALS."/{$goal->id}");
    expect($goal->fresh())->name->toBe('Mua căn hộ')->deadline->toBeNull()
        ->and(GoalContribution::count())->toBe(1);

    $this->delete(GOALS."/{$goal->id}")->assertRedirect(GOALS);
    expect(Goal::count())->toBe(0)->and(GoalContribution::count())->toBe(0);
});

it('deletes a single contribution', function () {
    $goal = makeGoal(saved: 2_000_000);

    $this->delete(GOALS."/{$goal->id}/gop/{$goal->contributions()->sole()->id}")->assertRedirect(GOALS."/{$goal->id}");

    expect($goal->fresh()->savedAmount())->toBe(0);
});

it('keeps goals private to their owner', function () {
    $goal = makeGoal(saved: 1_000_000);
    $contribution = $goal->contributions()->sole();
    $intruder = User::factory()->create();
    $this->actingAs($intruder);

    $this->get(GOALS)->assertOk()->assertDontSee('Mua nhà');
    $this->get(GOALS."/{$goal->id}")->assertNotFound();
    $this->get(GOALS."/{$goal->id}/edit")->assertNotFound();
    $this->put(GOALS."/{$goal->id}", ['name' => 'Bị sửa', 'target_amount' => '1', 'icon' => 'home', 'color' => 'blue'])->assertNotFound();
    $this->post(GOALS."/{$goal->id}/gop", ['direction' => 'gop', 'amount' => '1000', 'contributed_on' => '2026-10-30'])->assertNotFound();
    $this->delete(GOALS."/{$goal->id}/gop/{$contribution->id}")->assertNotFound();
    $this->delete(GOALS."/{$goal->id}")->assertNotFound();

    // Lần góp của người khác không gắn được vào mục tiêu của mình.
    $own = makeGoal(['name' => 'Của tôi']);
    $this->delete(GOALS."/{$own->id}/gop/{$contribution->id}")->assertNotFound();

    expect(Goal::withoutGlobalScopes()->find($goal->id)->name)->toBe('Mua nhà')
        ->and(GoalContribution::withoutGlobalScopes()->count())->toBe(1);
});

it('shows the empty state and hides the screen when the flag is off', function () {
    $this->get(GOALS)->assertOk()->assertSee('Chưa có mục tiêu nào');

    Feature::deactivate('personal');
    $this->get(GOALS)->assertNotFound();
});

it('counts calendar months left until the deadline', function (?string $deadline, ?int $months) {
    expect(makeGoal(['deadline' => $deadline])->monthsLeft())->toBe($months);
})->with([
    'không đặt hạn' => [null, null],
    'cuối tháng này' => ['2026-10-31', 1],
    'tháng sau' => ['2026-11-15', 1],
    'cuối năm sau' => ['2027-12-31', 14],
    'đã quá hạn' => ['2026-10-29', 0],
]);
