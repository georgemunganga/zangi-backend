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
        Schema::create('ticket_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('portal_user_id')->nullable()->constrained('portal_users')->nullOnDelete();
            $table->string('buyer_type', 32);
            $table->string('email')->index();
            $table->string('phone');
            $table->string('organization_name')->nullable();
            $table->string('event_slug');
            $table->string('event_title');
            $table->string('date_label');
            $table->string('time_label');
            $table->string('location_label');
            $table->string('ticket_type_id', 32);
            $table->string('ticket_type_label');
            $table->string('ticket_holder_name')->nullable();
            $table->string('buyer_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('currency', 8);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('status', 64)->default('Pending');
            $table->string('ticket_code')->nullable()->unique();
            $table->string('qr_path')->nullable();
            $table->string('pass_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_purchases');
    }
};
