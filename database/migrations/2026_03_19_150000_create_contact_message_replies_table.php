<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained('contact_messages')->cascadeOnDelete();
            $table->string('author_type', 32)->default('admin');
            $table->string('author_name');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_message_replies');
    }
};
