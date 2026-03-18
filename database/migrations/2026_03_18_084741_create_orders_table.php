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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('portal_user_id')->nullable()->constrained('portal_users')->nullOnDelete();
            $table->string('buyer_type', 32);
            $table->string('email')->index();
            $table->string('phone');
            $table->string('organization_name')->nullable();
            $table->string('product_slug');
            $table->string('product_title');
            $table->string('format', 32);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('currency', 8);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('status', 64)->default('Received');
            $table->json('timeline')->nullable();
            $table->unsignedInteger('current_step')->default(0);
            $table->string('payment_status', 64)->default('Pending');
            $table->string('payment_method', 64);
            $table->boolean('download_ready')->default(false);
            $table->string('download_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
