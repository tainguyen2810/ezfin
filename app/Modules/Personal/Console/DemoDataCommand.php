<?php

namespace App\Modules\Personal\Console;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\RecordTransaction;
use App\Modules\Personal\Actions\RecordTransfer;
use App\Modules\Personal\Enums\NetWorthKind;
use App\Modules\Personal\Enums\WalletType;
use App\Modules\Personal\Models\Budget;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\GoalContribution;
use App\Modules\Personal\Models\NetWorthItem;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dữ liệu mẫu cho ứng dụng quản lý tiền, để xem thử giao diện khi phát triển hoặc demo.
 * Chỉ chạy ngoài production: dữ liệu là giả lập, không được xuất hiện trên tài khoản thật.
 */
class DemoDataCommand extends Command
{
    public const DEMO_EMAIL = 'demo@ezfin.test';

    protected $signature = 'ezfin:demo-data {email? : Email tài khoản có sẵn; bỏ trống để dùng '.self::DEMO_EMAIL.'}
        {--fresh : Xóa dữ liệu tài chính hiện có của tài khoản trước khi tạo}
        {--months=6 : Số tháng giao dịch, tính cả tháng này}';

    protected $description = 'Tạo dữ liệu mẫu (ví, giao dịch, ngân sách, mục tiêu, tài sản & nợ) cho ứng dụng quản lý tiền';

    /** @var array<string, Category> */
    private array $categories = [];

    /** @var array<string, Wallet> */
    private array $wallets = [];

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Không tạo dữ liệu mẫu trên production.');

            return self::FAILURE;
        }

        $email = $this->argument('email') ?? self::DEMO_EMAIL;
        $user = User::where('email', $email)->first();
        $password = null;

        if (! $user) {
            if ($this->argument('email') !== null) {
                $this->error("Không có tài khoản [$email]. Đăng ký ở app.ezfin.test trước, hoặc bỏ trống email để dùng ".self::DEMO_EMAIL.'.');

                return self::FAILURE;
            }
            $password = Str::password(12, symbols: false);
            $user = User::create(['name' => 'Người dùng mẫu', 'email' => $email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->hasAnyRole(Role::values())) {
            $this->error('Đây là tài khoản quản trị, không tạo dữ liệu mẫu cho tài khoản này.');

            return self::FAILURE;
        }

        // Đăng nhập trong tiến trình console để trait BelongsToUser lọc và gán user_id như trong ứng dụng.
        Auth::setUser($user);

        if (Wallet::exists() || Goal::exists() || NetWorthItem::exists()) {
            if (! $this->option('fresh')) {
                $this->error("Tài khoản [$email] đã có dữ liệu. Thêm --fresh để xóa và tạo lại.");

                return self::FAILURE;
            }
            if (! $this->confirm("Xóa toàn bộ ví, giao dịch, ngân sách, mục tiêu, tài sản & nợ của [$email]?", true)) {
                return self::FAILURE;
            }
        }

        $months = max(1, min(24, (int) $this->option('months')));
        mt_srand(crc32($email));

        DB::transaction(function () use ($user, $months) {
            $this->wipe();
            app(CreateDefaultCategories::class)->handle($user);
            $this->categories = Category::get()->keyBy('name')->all();
            $this->createWallets();
            $this->createTransactions($months);
            $this->createBudgets();
            $this->createGoals();
            $this->createNetWorth();
        });

        $this->info("Đã tạo dữ liệu mẫu cho [$email]: ".Transaction::count().' giao dịch, '.Wallet::count().' ví, '
            .Budget::count().' ngân sách, '.Goal::count().' mục tiêu, '.NetWorthItem::count().' tài sản & nợ.');
        if ($password !== null) {
            $this->line("Đăng nhập app.ezfin.test với email $email, mật khẩu: $password");
        }

        return self::SUCCESS;
    }

    private function wipe(): void
    {
        Transaction::query()->delete();
        Budget::query()->delete();
        GoalContribution::query()->delete();
        Goal::query()->delete();
        NetWorthItem::query()->delete();
        Wallet::query()->delete();
    }

    private function createWallets(): void
    {
        foreach ([
            'cash' => ['Tiền mặt', WalletType::Cash, null, 2_000_000],
            'bank' => ['Tài khoản lương', WalletType::Bank, 'Vietcombank', 38_000_000],
            'ewallet' => ['Ví MoMo', WalletType::EWallet, 'MoMo', 800_000],
            'savings' => ['Sổ tiết kiệm 12 tháng', WalletType::Savings, 'Techcombank', 100_000_000],
        ] as $key => [$name, $type, $institution, $opening]) {
            $this->wallets[$key] = Wallet::create(['name' => $name, 'type' => $type, 'institution' => $institution,
                'opening_balance' => $opening, 'include_in_total' => true]);
        }
    }

    private function createTransactions(int $months): void
    {
        $today = CarbonImmutable::today();
        $first = $today->startOfMonth()->subMonthsNoOverflow($months - 1);

        for ($month = $first; $month <= $today; $month = $month->addMonthNoOverflow()) {
            $on = fn (int $day) => $month->setDay(min($day, $month->daysInMonth));
            $isCurrent = $month->isSameMonth($today);

            // Thu nhập
            $this->income('Lương', 'bank', 25_000_000, $on(5), 'Lương tháng '.$month->subMonthNoOverflow()->format('m/Y'));
            $this->income('Lãi tiết kiệm', 'savings', 425_000, $on(28), 'Lãi sổ tiết kiệm');
            if ($month->month % 2 === 0) {
                $this->income('Kinh doanh', 'bank', $this->amount(2_000_000, 4_500_000, 100_000), $on(18), 'Bán hàng online');
            }
            if ($month->month === 12 || $month->month === 6) {
                $this->income('Thưởng', 'bank', 12_000_000, $on(20), 'Thưởng '.($month->month === 12 ? 'cuối năm' : 'giữa năm'));
            }

            // Chuyển tiền giữa các ví
            $this->transfer('bank', 'cash', 3_000_000, $on(6), 'Rút tiền mặt');
            $this->transfer('bank', 'ewallet', 1_500_000, $on(6), 'Nạp ví MoMo');
            $this->transfer('bank', 'savings', 5_000_000, $on(7), 'Gửi thêm tiết kiệm');

            // Chi cố định
            $this->expense('Nhà ở', 'bank', 6_000_000, $on(1), 'Tiền thuê nhà');
            $this->expense('Hóa đơn & tiện ích', 'bank', $this->amount(650_000, 1_050_000, 1_000), $on(10), 'Tiền điện');
            $this->expense('Hóa đơn & tiện ích', 'bank', $this->amount(90_000, 160_000, 1_000), $on(10), 'Tiền nước');
            $this->expense('Hóa đơn & tiện ích', 'ewallet', 220_000, $on(12), 'Internet');
            $this->expense('Hóa đơn & tiện ích', 'ewallet', 150_000, $on(12), 'Cước điện thoại');
            $this->expense('Gia đình', 'bank', 2_000_000, $on(8), 'Gửi bố mẹ');
            $this->expense('Học tập', 'bank', 490_000, $on(15), 'Khóa học tiếng Anh online');

            // Chi hằng ngày
            $lastDay = $isCurrent ? $today->day : $month->daysInMonth;
            for ($day = 1; $day <= $lastDay; $day++) {
                $date = $on($day);
                $this->expense('Ăn uống', mt_rand(0, 2) ? 'cash' : 'ewallet', $this->amount(35_000, 75_000, 5_000), $date, 'Ăn trưa');
                if (mt_rand(1, 3) === 1) {
                    $this->expense('Ăn uống', 'ewallet', $this->amount(25_000, 60_000, 5_000), $date, 'Cà phê');
                }
                if ($date->isWeekend()) {
                    $this->expense('Ăn uống', 'bank', $this->amount(250_000, 650_000, 10_000), $date, 'Đi chợ, siêu thị');
                }
                if (mt_rand(1, 6) === 1) {
                    $this->expense('Đi lại', 'cash', $this->amount(70_000, 90_000, 5_000), $date, 'Đổ xăng');
                }
                if (mt_rand(1, 8) === 1) {
                    $this->expense('Đi lại', 'ewallet', $this->amount(35_000, 120_000, 1_000), $date, 'Grab');
                }
            }

            // Chi không đều
            foreach (range(1, mt_rand(2, 4)) as $i) {
                $this->expense('Mua sắm', 'bank', $this->amount(150_000, 700_000, 10_000), $on(mt_rand(1, $lastDay)),
                    ['Quần áo', 'Đồ gia dụng', 'Mỹ phẩm', 'Sách'][mt_rand(0, 3)]);
            }
            foreach (range(1, mt_rand(1, 3)) as $i) {
                $this->expense('Giải trí', 'ewallet', $this->amount(120_000, 400_000, 10_000), $on(mt_rand(1, $lastDay)),
                    ['Xem phim', 'Đi chơi cuối tuần', 'Netflix', 'Karaoke'][mt_rand(0, 3)]);
            }
            if (mt_rand(1, 2) === 1) {
                $this->expense('Sức khỏe', 'cash', $this->amount(150_000, 600_000, 10_000), $on(mt_rand(1, $lastDay)), 'Thuốc, khám bệnh');
            }
            if ($isCurrent) {
                // Một khoản mua lớn trong tháng để thấy cảnh báo vượt ngân sách
                $this->expense('Mua sắm', 'bank', 1_890_000, $on(min(3, $lastDay)), 'Tai nghe mới');
            }
            if ($month->month % 3 === 0) {
                $this->expense('Đi lại', 'bank', 650_000, $on(22), 'Bảo dưỡng xe máy');
            }
        }
    }

    private function createBudgets(): void
    {
        $plan = ['Ăn uống' => 5_500_000, 'Nhà ở' => 6_000_000, 'Đi lại' => 1_200_000, 'Mua sắm' => 3_500_000,
            'Hóa đơn & tiện ích' => 1_500_000, 'Giải trí' => 800_000, 'Sức khỏe' => 500_000, 'Gia đình' => 2_000_000];
        foreach ([CarbonImmutable::today()->startOfMonth()->subMonthNoOverflow(), CarbonImmutable::today()->startOfMonth()] as $month) {
            foreach ($plan as $name => $amount) {
                Budget::create(['category_id' => $this->categories[$name]->id, 'month' => $month->toDateString(), 'amount' => $amount]);
            }
        }
    }

    private function createGoals(): void
    {
        $today = CarbonImmutable::today();
        foreach ([
            ['Quỹ dự phòng 6 tháng', 120_000_000, $today->addMonthsNoOverflow(14), 'shield-check', 'green', [30_000_000, 5_000_000, 5_000_000, 5_000_000, 5_000_000]],
            ['Du lịch Đà Nẵng', 15_000_000, $today->addMonthsNoOverflow(4), 'globe-asia-australia', 'blue', [3_000_000, 2_000_000, 2_000_000, 1_500_000]],
            ['Mua xe máy mới', 45_000_000, $today->addMonthsNoOverflow(10), 'truck', 'orange', [10_000_000, 3_000_000, 3_000_000]],
            ['Mua máy tính bảng', 22_000_000, $today->subMonthsNoOverflow(1), 'device-phone-mobile', 'violet', [12_000_000, 5_000_000, 5_000_000]],
        ] as [$name, $target, $deadline, $icon, $color, $contributions]) {
            $goal = Goal::create(['name' => $name, 'target_amount' => $target, 'deadline' => $deadline->toDateString(), 'icon' => $icon, 'color' => $color]);
            foreach ($contributions as $i => $amount) {
                $goal->contributions()->create(['amount' => $amount,
                    'contributed_on' => $today->subMonthsNoOverflow(count($contributions) - $i)->setDay(7)->toDateString(),
                    'note' => $i === 0 ? 'Số đã có khi tạo mục tiêu' : null]);
            }
        }
    }

    private function createNetWorth(): void
    {
        $valued = CarbonImmutable::today()->subMonthNoOverflow()->toDateString();
        foreach ([
            [NetWorthKind::Vehicle, 'Xe máy Honda Vision', 28_000_000, null, null],
            [NetWorthKind::Gold, 'Vàng nhẫn 2 chỉ', 16_800_000, null, null],
            [NetWorthKind::Investment, 'Chứng chỉ quỹ mở', 35_000_000, null, null],
            [NetWorthKind::Receivable, 'Bạn mượn', 5_000_000, null, null],
            [NetWorthKind::CreditCard, 'Dư nợ thẻ tín dụng', 4_350_000, null, 4_350_000],
            [NetWorthKind::ConsumerLoan, 'Trả góp điện thoại', 9_600_000, 0, 1_600_000],
        ] as [$kind, $name, $amount, $rate, $payment]) {
            NetWorthItem::create(['kind' => $kind, 'name' => $name, 'amount' => $amount, 'interest_rate' => $rate,
                'monthly_payment' => $payment, 'valued_on' => $valued, 'note' => null]);
        }
    }

    private function income(string $category, string $wallet, int $amount, CarbonImmutable $date, string $note): void
    {
        $this->record('income', $category, $wallet, $amount, $date, $note);
    }

    private function expense(string $category, string $wallet, int $amount, CarbonImmutable $date, string $note): void
    {
        $this->record('expense', $category, $wallet, $amount, $date, $note);
    }

    private function record(string $type, string $category, string $wallet, int $amount, CarbonImmutable $date, string $note): void
    {
        if ($date->isFuture()) {
            return;
        }
        app(RecordTransaction::class)->handle(['type' => $type, 'wallet_id' => $this->wallets[$wallet]->id,
            'category_id' => $this->categories[$category]->id, 'amount' => $amount, 'occurred_on' => $date->toDateString(), 'note' => $note]);
    }

    private function transfer(string $from, string $to, int $amount, CarbonImmutable $date, string $note): void
    {
        if ($date->isFuture()) {
            return;
        }
        app(RecordTransfer::class)->handle(['from_wallet_id' => $this->wallets[$from]->id, 'to_wallet_id' => $this->wallets[$to]->id,
            'amount' => $amount, 'occurred_on' => $date->toDateString(), 'note' => $note]);
    }

    /** Số ngẫu nhiên trong khoảng, làm tròn theo bước (tiền thật thường tròn nghìn). */
    private function amount(int $min, int $max, int $step): int
    {
        return intdiv(mt_rand($min, $max), $step) * $step;
    }
}
