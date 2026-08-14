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
            $table->timestamp('claim_confirmed_at')->nullable()->after('claimed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_reports', function (Blueprint $table) {
            $table->dropColumn('claim_confirmed_at');
        });
    }
};
