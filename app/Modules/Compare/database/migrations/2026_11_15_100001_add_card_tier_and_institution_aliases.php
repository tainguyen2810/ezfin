<?php

use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\CardTier;
use App\Modules\Compare\Support\InstitutionAliases;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_card_details', function (Blueprint $table) {
            $table->string('tier', 20)->nullable()->after('network')->index();
        });

        Schema::table('financial_institutions', function (Blueprint $table) {
            $table->string('search_aliases')->nullable()->after('short_name');
        });

        foreach (InstitutionAliases::KNOWN as $slug => $aliases) {
            DB::table('financial_institutions')->where('slug', $slug)->whereNull('search_aliases')->update(['search_aliases' => $aliases]);
        }

        // Điền hạng và tổ chức thẻ còn trống theo tên thẻ đã có.
        DB::table('credit_card_details as card')
            ->join('financial_products as product', 'product.id', '=', 'card.product_id')
            ->select('card.product_id', 'card.network', 'product.name')
            ->orderBy('card.product_id')
            ->each(function (object $row) {
                DB::table('credit_card_details')->where('product_id', $row->product_id)->update(array_filter([
                    'tier' => CardTier::fromName($row->name)?->value,
                    'network' => $row->network ?? CardNetwork::fromName($row->name)?->value,
                ]));
            });
    }

    public function down(): void
    {
        Schema::table('credit_card_details', function (Blueprint $table) {
            $table->dropIndex(['tier']);
            $table->dropColumn('tier');
        });
        Schema::table('financial_institutions', fn (Blueprint $table) => $table->dropColumn('search_aliases'));
    }
};
