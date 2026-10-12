<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-editable shop settings: service charge rates, quick amounts, the
 * auto-close cutoff, and a note of the last backup.
 *
 * config/gtrack.php still holds the defaults. A row here overrides a default,
 * and deleting the row puts the default back — so a fresh install with no rows
 * behaves exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
