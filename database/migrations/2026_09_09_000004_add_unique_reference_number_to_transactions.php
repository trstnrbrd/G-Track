<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A GCash reference number identifies exactly one real transfer, so the same
     * one must never appear twice in the books.
     *
     * Without this, a distracted operator can record the same cash out twice —
     * the balances move twice for money that only moved once, and nothing in the
     * app would ever flag it. The form checks for duplicates too, but only the
     * database can win a race between two simultaneous submits.
     *
     * The column is nullable, and both MySQL and SQLite permit unlimited NULLs
     * in a unique index, so cash-ins recorded without a reference are unaffected.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['reference_number']);
        });
    }
};
