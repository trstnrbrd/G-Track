<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Where the service charge landed:
            //   'cash'  => customer paid the fee with physical cash (hiwalay na bayad)
            //   'gcash' => the fee was settled through GCash (bawas sa padala, or
            //              included in the amount the customer sent)
            // See Transaction::balanceDeltas() for how this drives the balances.
            $table->enum('charge_paid_in', ['cash', 'gcash'])
                ->default('cash')
                ->after('service_charge');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('charge_paid_in');
        });
    }
};
