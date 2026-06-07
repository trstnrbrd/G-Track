<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table holding the current, real-time balances.
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->decimal('gcash_balance', 14, 2)->default(0);
            $table->decimal('cash_balance', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
