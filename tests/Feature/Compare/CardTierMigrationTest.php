<?php

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('backfills tiers, skips cards whose name says nothing, and can run again after a half-finished run', function () {
    $migration = require base_path('app/Modules/Compare/database/migrations/2026_11_15_100001_add_card_tier_and_institution_aliases.php');
    $bank = FinancialInstitution::factory()->create(['slug' => 'vietcombank', 'search_aliases' => null]);
    $gold = FinancialProduct::factory()->create(['name' => 'Visa Gold', 'institution_id' => $bank->id]);
    $plain = FinancialProduct::factory()->create(['name' => 'Thẻ Express', 'institution_id' => $bank->id]);
    DB::table('credit_card_details')->insert(['product_id' => $gold->id, 'network' => null]);
    DB::table('credit_card_details')->insert(['product_id' => $plain->id, 'network' => 'jcb']);

    // Cột đã có sẵn (lần chạy trước dừng giữa chừng): chạy lại không lỗi.
    $migration->up();

    expect(DB::table('credit_card_details')->where('product_id', $gold->id)->first())
        ->tier->toBe('gold')->network->toBe('visa')
        ->and(DB::table('credit_card_details')->where('product_id', $plain->id)->first())
        ->tier->toBeNull()->network->toBe('jcb');
});
