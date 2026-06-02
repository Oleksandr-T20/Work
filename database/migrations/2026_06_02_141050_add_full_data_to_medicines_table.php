<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite не підтримує додавання кількох колонок за один ALTER TABLE
        // і не підтримує ->after() — тому додаємо по одній через окремі виклики
        Schema::table('medicines', function (Blueprint $table) {
            $table->text('symptoms')->nullable();
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->json('active_ingredients')->nullable();
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->string('min_age')->nullable();
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->boolean('pregnancy_safe')->nullable();
        });
    }

    public function down(): void
    {
        // SQLite не підтримує DROP COLUMN напряму — відтворюємо таблицю без цих колонок
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn(['symptoms', 'active_ingredients', 'min_age', 'pregnancy_safe']);
        });
    }
};
