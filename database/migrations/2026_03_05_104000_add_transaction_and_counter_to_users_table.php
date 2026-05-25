<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $isSqlite = DB::getDriverName() === 'sqlite';

            if (! Schema::hasColumn('users', 'transaction_id')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('transaction_id')->nullable();
                } else {
                    $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
                }
            }
            if (! Schema::hasColumn('users', 'counter_id')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('counter_id')->nullable();
                } else {
                    $table->foreignId('counter_id')->nullable()->constrained('counters')->onDelete('set null');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
                $table->dropColumn('transaction_id');
            }
            if (Schema::hasColumn('users', 'counter_id')) {
                $table->dropForeign(['counter_id']);
                $table->dropColumn('counter_id');
            }
        });
    }
};
