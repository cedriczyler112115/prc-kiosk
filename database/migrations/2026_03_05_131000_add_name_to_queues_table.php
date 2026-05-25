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
            if (! Schema::hasColumn('queues', 'name')) {
                $table->string('name', 150)->nullable()->after('queue_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('queues')) {
            return;
        }
        Schema::table('queues', function (Blueprint $table) {
            if (Schema::hasColumn('queues', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
