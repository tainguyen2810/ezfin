<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Support\EnvFile;
use App\Modules\Core\Support\Installer;
use Dotenv\Dotenv;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Máy test có thể thiếu public/build: coi môi trường là đủ điều kiện, trừ khi truyền danh sách kiểm tra. */
function fakeRequirements(array $checks = []): Installer
{
    $installer = new class($checks) extends Installer
    {
        public function __construct(private array $checks) {}

        public function requirements(): array
        {
            return $this->checks;
        }
    };

    app()->instance(Installer::class, $installer);

    return $installer;
}

function installToken(): string
{
    return app(Installer::class)->token();
}

beforeEach(function () {
    // .env của trang cài đặt trỏ sang thư mục tạm để test không đụng .env thật.
    $this->envDir = sys_get_temp_dir().'/ezfin-install-'.uniqid();
    mkdir($this->envDir);
    file_put_contents($this->envDir.'/.env', "APP_KEY=base64:dGVzdA==\nDB_CONNECTION=sqlite\n");
    app()->useEnvironmentPath($this->envDir);
    fakeRequirements();
});

afterEach(function () {
    @unlink(storage_path('app/ezfin-installed'));
    @unlink(storage_path('app/ezfin-install.token'));
    @unlink($this->envDir.'/.env');
    @rmdir($this->envDir);
});

it('shows the admin step once the database is ready', function () {
    $this->get('/cai-dat')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Tài khoản quản trị')
        ->assertSee('action="'.route('install.admin').'"', false);
});

it('lists failing requirements first', function () {
    fakeRequirements([['label' => 'Extension PHP intl', 'ok' => false, 'hint' => 'Bật intl trong WAMP.']]);

    $this->get('/cai-dat')->assertOk()->assertSee('Môi trường')->assertSee('Bật intl trong WAMP.')->assertDontSee('install.admin');
});

it('rejects a form without the install token', function () {
    $this->post('/cai-dat/quan-tri', [
        'name' => 'Tai', 'email' => 'admin@ezfin.vn', 'password' => 'MatKhauRatDai123', 'password_confirmation' => 'MatKhauRatDai123',
    ])->assertStatus(419);

    $this->post('/cai-dat/quan-tri', ['_install' => 'sai', 'name' => 'Tai'])->assertStatus(419);

    expect(User::count())->toBe(0);
});

it('creates the first super admin and then locks itself', function () {
    $this->post('/cai-dat/quan-tri', [
        '_install' => installToken(),
        'name' => 'Tai Nguyen',
        'email' => 'admin@ezfin.vn',
        'password' => 'MatKhauRatDai123',
        'password_confirmation' => 'MatKhauRatDai123',
    ])->assertRedirect(Filament::getPanel('admin')->getLoginUrl());

    $user = User::firstWhere('email', 'admin@ezfin.vn');
    expect($user->hasRole(Role::SuperAdmin->value))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(is_file(storage_path('app/ezfin-installed')))->toBeTrue();

    $this->get('/cai-dat')->assertNotFound();
    $this->post('/cai-dat/quan-tri', ['_install' => 'bất kỳ'])->assertNotFound();
});

it('stays locked when an admin was created from the command line', function () {
    User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->get('/cai-dat')->assertNotFound();
});

it('requires a strong confirmed password', function () {
    $this->post('/cai-dat/quan-tri', [
        '_install' => installToken(),
        'name' => 'Tai',
        'email' => 'admin@ezfin.vn',
        'password' => 'ngan',
        'password_confirmation' => 'khac',
    ])->assertStatus(422)->assertSee('role="alert"', false)->assertSee('admin@ezfin.vn');

    expect(User::count())->toBe(0);
});

it('reports a database connection error without touching .env', function () {
    $this->post('/cai-dat/database', [
        '_install' => installToken(),
        'host' => '127.0.0.1',
        'port' => 1,
        'database' => 'ezfin',
        'username' => 'root',
    ])->assertStatus(422)->assertSee('Không kết nối được MySQL');

    expect(file_get_contents($this->envDir.'/.env'))->not->toContain('DB_HOST');
});

it('rejects unsafe database names', function () {
    $this->post('/cai-dat/database', [
        '_install' => installToken(),
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'ezfin`; DROP',
        'username' => 'root',
    ])->assertStatus(422)->assertSee('Tên database chỉ gồm');
});

it('creates .env and APP_KEY when missing', function () {
    unlink($this->envDir.'/.env');
    copy(base_path('.env.example'), $this->envDir.'/.env');

    $this->get('/cai-dat')->assertOk();

    expect((new EnvFile($this->envDir.'/.env'))->get('APP_KEY'))->toStartWith('base64:');
});

it('updates, uncomments and quotes .env values', function () {
    $path = $this->envDir.'/.env';
    file_put_contents($path, "APP_NAME=EzFin\n# DB_HOST=127.0.0.1\nDB_PASSWORD=\n");
    $env = new EnvFile($path);

    $env->set(['DB_HOST' => 'localhost', 'DB_PASSWORD' => 'p@ss "w$rd" #1\\', 'DB_PORT' => '3306']);

    $parsed = Dotenv::parse(file_get_contents($path));
    expect($parsed)->toMatchArray(['APP_NAME' => 'EzFin', 'DB_HOST' => 'localhost', 'DB_PASSWORD' => 'p@ss "w$rd" #1\\', 'DB_PORT' => '3306'])
        ->and($env->get('DB_PASSWORD'))->toBe('p@ss "w$rd" #1\\')
        ->and(substr_count(file_get_contents($path), 'DB_HOST'))->toBe(1);
});

it('requires admin 2FA everywhere except on a local machine', function () {
    $evaluate = function (string $appEnv, ?string $flag = null): bool {
        $backup = [$_SERVER['APP_ENV'] ?? null, $_SERVER['EZFIN_ADMIN_2FA'] ?? null];
        $_SERVER['APP_ENV'] = $appEnv;
        if ($flag === null) {
            unset($_SERVER['EZFIN_ADMIN_2FA']);
        } else {
            $_SERVER['EZFIN_ADMIN_2FA'] = $flag;
        }

        try {
            return (require config_path('ezfin.php'))['admin_2fa'];
        } finally {
            [$_SERVER['APP_ENV'], $_SERVER['EZFIN_ADMIN_2FA']] = $backup;
            if ($backup[1] === null) {
                unset($_SERVER['EZFIN_ADMIN_2FA']);
            }
        }
    };

    expect($evaluate('production'))->toBeTrue()
        ->and($evaluate('production', 'false'))->toBeTrue()
        ->and($evaluate('staging'))->toBeTrue()
        ->and($evaluate('local'))->toBeFalse()
        ->and($evaluate('local', 'true'))->toBeTrue()
        ->and(config('ezfin.admin_2fa'))->toBeTrue();
});
