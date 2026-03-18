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
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('purchase_type', 32);
            $table->unsignedBigInteger('purchase_id')->nullable()->index();
            $table->string('buyer_type', 32);
            $table->string('email')->index();
            $table->string('currency', 8);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 64);
            $table->string('status', 64)->default('Pending');
            $table->json('lenco_payload')->nullable();
            $table->json('lenco_response')->nullable();
            $table->string('return_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
