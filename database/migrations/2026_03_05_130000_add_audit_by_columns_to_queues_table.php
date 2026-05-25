<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queues')) {
            return;
        }

        Schema::table('queues', function (Blueprint $table) {
            $isSqlite = DB::getDriverName() === 'sqlite';

            if (! Schema::hasColumn('queues', 'called_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('called_by')->nullable()->index();
                } else {
                    $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('queues', 'serving_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('serving_by')->nullable()->index();
                } else {
                    $table->foreignId('serving_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('queues', 'completed_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('completed_by')->nullable()->index();
                } else {
                    $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('queues', 'skipped_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('skipped_by')->nullable()->index();
                } else {
                    $table->foreignId('skipped_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('queues', 'cancelled_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('cancelled_by')->nullable()->index();
                } else {
                    $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('queues')) {
            return;
        }

        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'called_by')) {
                $table->dropColumn('called_by');
            }
            if (Schema::hasColumn('queues', 'serving_by')) {
                $table->dropColumn('serving_by');
            }
            if (Schema::hasColumn('queues', 'completed_by')) {
                $table->dropColumn('completed_by');
            }
            if (Schema::hasColumn('queues', 'skipped_by')) {
                $table->dropColumn('skipped_by');
            }
            if (Schema::hasColumn('queues', 'cancelled_by')) {
                $table->dropColumn('cancelled_by');
            }
        });
    }
};
