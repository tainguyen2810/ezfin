<?php

namespace App\Modules\Personal;

use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use App\Modules\Personal\Policies\OwnedRecordPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

/** Ứng dụng quản lý tiền trên app.ezfin.vn: tài khoản khách hàng, ví, giao dịch, ngân sách, mục tiêu... */
class PersonalServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'personal';

    public function boot(): void
    {
        parent::boot();

        foreach ([Wallet::class, Category::class, Transaction::class] as $model) {
            Gate::policy($model, OwnedRecordPolicy::class);
        }

        RateLimiter::for('personal-auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
