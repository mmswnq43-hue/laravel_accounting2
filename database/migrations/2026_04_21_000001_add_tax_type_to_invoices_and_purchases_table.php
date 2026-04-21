<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // vat_15 = خاضع للضريبة بنسبة 15%, vat_0 = خاضع بنسبة 0%, exempt = معفي, out_of_scope = خارج النطاق
            $table->string('tax_type', 30)->nullable()->default('vat_15')->after('tax_amount');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('tax_type', 30)->nullable()->default('vat_15')->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('tax_type');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('tax_type');
        });
    }
};
