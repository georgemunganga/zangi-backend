<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->string('pricing_round_key', 64)->nullable()->after('ticket_type_label');
            $table->string('pricing_round_label')->nullable()->after('pricing_round_key');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropColumn(['pricing_round_key', 'pricing_round_label']);
        });
    }
};
