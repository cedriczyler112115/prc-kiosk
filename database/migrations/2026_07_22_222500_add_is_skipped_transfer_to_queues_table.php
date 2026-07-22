<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('queues', 'is_skipped_transfer')) {
            Schema::table('queues', function (Blueprint $table) {
                $table->boolean('is_skipped_transfer')->default(false)->after('is_transfer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('queues', 'is_skipped_transfer')) {
            Schema::table('queues', function (Blueprint $table) {
                $table->dropColumn('is_skipped_transfer');
            });
        }
    }
};
