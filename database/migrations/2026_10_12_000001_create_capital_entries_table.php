<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's puhunan (capital) and utang (what has been taken out of it).
 *
 * One running ledger, not separate loans: puhunan = sum of `capital`,
 * utang = sum of `borrow` minus sum of `repay`. Kept apart from
 * `transactions` so none of it reaches the cash in/out totals, History,
 * earnings or the PDFs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_entries', function (Blueprint $table) {
            $table->id();
            // Set when a day is open. Entries that move nothing can be written
            // at night after End Day, so this is nullable.
            $table->foreignId('daily_session_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 10); // borrow | repay | capital
            $table->decimal('amount', 12, 2);
            // Which balance physically moved: cash | gcash, or NULL when
            // nothing moved (e.g. repaid out of earnings already in the wallet).
            $table->string('balance', 10)->nullable();
            $table->string('purpose', 20)->nullable(); // grocery | kuryente | tubig | iba
            $table->string('note', 100)->nullable();
            $table->timestamps();

            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_entries');
    }
};
