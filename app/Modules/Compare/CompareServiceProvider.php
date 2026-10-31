<?php

namespace App\Modules\Compare;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Policies\FinancialInstitutionPolicy;
use App\Modules\Compare\Policies\FinancialProductPolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavItem;
use App\Support\Seo\SitemapUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CompareServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'compare';

    protected function publicNavigation(): array
    {
        return [
            new NavItem('Lãi suất tiết kiệm', 'compare.savings.index', order: 10),
            new NavItem('Thẻ tín dụng', 'compare.credit-cards.index', order: 20),
            new NavItem('Tài khoản ngân hàng', 'compare.bank-accounts.index', order: 30),
            new NavItem('Ngân hàng', 'compare.institutions.index', order: 35),
        ];
    }

    protected function sitemap(): iterable
    {
        yield new SitemapUrl(route('compare.savings.index'));
        yield new SitemapUrl(route('compare.credit-cards.index'));
        yield new SitemapUrl(route('compare.bank-accounts.index'));
        yield new SitemapUrl(route('compare.savings.table'));
        yield new SitemapUrl(route('compare.institutions.index'));

        // Trang ngân hàng: cùng điều kiện với InstitutionController::show (còn hoạt động, có sản phẩm đã xuất bản).
        $institutions = FinancialInstitution::query()->active()
            ->whereHas('products', fn (Builder $query) => $query->published())
            ->orderBy('slug')
            ->get(['id', 'slug', 'updated_at']);
        foreach ($institutions as $institution) {
            yield new SitemapUrl(route('compare.institutions.show', $institution->slug), $institution->updated_at);
        }

        // Cùng điều kiện với trang chi tiết: đã xuất bản, tổ chức còn hoạt động, có thông số.
        foreach ([
            [ProductType::CreditCard, 'creditCard', 'compare.credit-cards.show'],
            [ProductType::BankAccount, 'bankAccount', 'compare.bank-accounts.show'],
        ] as [$type, $details, $route]) {
            $products = FinancialProduct::query()->ofType($type)->published()->indexable()
                ->whereHas('institution', fn (Builder $query) => $query->active())
                ->has($details)
                ->orderBy('slug')
                ->get(['id', 'slug', 'updated_at']);

            foreach ($products as $product) {
                yield new SitemapUrl(route($route, $product->slug), $product->updated_at);
            }
        }
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(FinancialInstitution::class, FinancialInstitutionPolicy::class);
        Gate::policy(FinancialProduct::class, FinancialProductPolicy::class);
    }
}
