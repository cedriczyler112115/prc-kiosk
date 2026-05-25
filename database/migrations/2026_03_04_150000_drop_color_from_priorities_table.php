<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('priorities')) {
            return;
        }

        if (! Schema::hasColumn('priorities', 'color')) {
            return;
        }

        Schema::table('priorities', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('priorities')) {
            return;
        }

        if (Schema::hasColumn('priorities', 'color')) {
            return;
        }

        Schema::table('priorities', function (Blueprint $table) {
            $table->string('color', 30)->nullable()->after('priority_level');
        });
    }
};
