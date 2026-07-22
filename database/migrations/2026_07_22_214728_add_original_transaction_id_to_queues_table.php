<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('queues', 'original_transaction_id')) {
            Schema::table('queues', function (Blueprint $table) {
                $table->unsignedBigInteger('original_transaction_id')->nullable()->after('transaction_id');
                $table->foreign('original_transaction_id')->references('id')->on('transactions')->nullOnDelete();
            });
        }

        // Backfill original_transaction_id with transaction_id for existing tickets
        DB::table('queues')->whereNull('original_transaction_id')->update([
            'original_transaction_id' => DB::raw('transaction_id'),
        ]);

        if (Schema::hasTable('queue_transfers')) {
            $transfers = DB::table('queue_transfers')
                ->select('queue_id', 'from_transaction_id')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($transfers as $transfer) {
                DB::table('queues')
                    ->where('id', $transfer->queue_id)
                    ->update(['original_transaction_id' => $transfer->from_transaction_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('queues', 'original_transaction_id')) {
            Schema::table('queues', function (Blueprint $table) {
                $table->dropForeign(['original_transaction_id']);
                $table->dropColumn('original_transaction_id');
            });
        }
    }
};
