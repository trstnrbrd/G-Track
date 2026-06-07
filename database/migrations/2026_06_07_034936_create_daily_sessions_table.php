<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A business "day". Start Day opens one (status=active); End Day closes it.
        // Transactions are only allowed while a session is active.
        Schema::create('daily_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('opening_gcash_balance', 14, 2);
            $table->decimal('opening_cash_balance', 14, 2);
            $table->decimal('closing_gcash_balance', 14, 2)->nullable();
            $table->decimal('closing_cash_balance', 14, 2)->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sessions');
    }
};
