<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store items a customer paid for with GCash.
 *
 * Kept apart from `transactions` on purpose: these aren't GCash services, earn
 * no service charge, and must not show up in the cash in / cash out totals,
 * the history page or its PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_session_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            // One GCash reference = one real transfer, same as transactions.
            $table->string('reference_number', 50)->nullable()->unique();
            $table->string('note', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_sales');
    }
};
