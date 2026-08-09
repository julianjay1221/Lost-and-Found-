<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const DEFAULT_CATEGORIES = [
        'Accessories',
        'Bags',
        'Books',
        'Clothing',
        'Drinkware',
        'Electronics',
        'IDs & Documents',
        'Keys',
        'Personal Items',
        'School Supplies',
        'Sports Equipment',
        'Wallets & Money',
    ];

    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_key')->unique();
            $table->timestamps();
        });

        $categories = collect(self::DEFAULT_CATEGORIES);

        if (Schema::hasTable('item_reports')) {
            $categories = $categories->merge(
                DB::table('item_reports')
                    ->select('category')
                    ->distinct()
                    ->pluck('category')
            );
        }

        $now = now();

        $rows = $categories
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->unique(fn ($category) => Str::lower($category))
            ->map(fn ($category) => [
                'name' => $category,
                'name_key' => Str::lower($category),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('item_categories')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};
