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
        Schema::create('item_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->index();
            $table->string('item_name');
            $table->string('category')->index();
            $table->dateTime('happened_at')->nullable()->index();
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->boolean('is_spam')->default(false)->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_reports');
    }
};
