<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropColumn('used_at');
        });
    }
};
