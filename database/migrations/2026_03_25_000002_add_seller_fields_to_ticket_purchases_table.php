<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('portal_user_id')->constrained('sellers')->nullOnDelete();
            $table->string('seller_code', 32)->nullable()->after('seller_id');
            $table->boolean('synced')->default(true)->after('source');
            $table->boolean('email_sent')->default(false)->after('synced');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['seller_id', 'seller_code', 'synced', 'email_sent']);
        });
    }
};
