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
        Schema::table('item_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('matched_report_id')->nullable()->index()->after('claimed_at');
            $table->timestamp('archived_at')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_reports', function (Blueprint $table) {
            $table->dropIndex(['matched_report_id']);
            $table->dropColumn(['matched_report_id', 'archived_at']);
        });
    }
};
