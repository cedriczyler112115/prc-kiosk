<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queues')) {
            return;
        }

        Schema::table('queues', function (Blueprint $table) {
            if (! Schema::hasColumn('queues', 'transfer_service_started_at')) {
                $table->timestamp('transfer_service_started_at')->nullable()->after('serving_at');
            }
            if (! Schema::hasColumn('queues', 'transfer_service_completed_at')) {
                $table->timestamp('transfer_service_completed_at')->nullable()->after('transfer_service_started_at');
            }
            if (! Schema::hasColumn('queues', 'transfer_service_time_seconds')) {
                $table->integer('transfer_service_time_seconds')->nullable()->after('service_time_seconds');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('queues')) {
            return;
        }
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'transfer_service_started_at')) {
                $table->dropColumn('transfer_service_started_at');
            }
            if (Schema::hasColumn('queues', 'transfer_service_completed_at')) {
                $table->dropColumn('transfer_service_completed_at');
            }
            if (Schema::hasColumn('queues', 'transfer_service_time_seconds')) {
                $table->dropColumn('transfer_service_time_seconds');
            }
        });
    }
};
