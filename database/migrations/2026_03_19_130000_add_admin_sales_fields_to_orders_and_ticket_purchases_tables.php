<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_name')->nullable()->after('organization_name');
            $table->string('source', 32)->default('online')->after('payment_method');
            $table->text('admin_notes')->nullable()->after('download_path');
        });

        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->string('source', 32)->default('online')->after('status');
            $table->text('admin_notes')->nullable()->after('pass_path');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'admin_notes',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_name',
                'source',
                'admin_notes',
            ]);
        });
    }
};
