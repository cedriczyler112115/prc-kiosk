<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('counters')) {
            return;
        }

        $hasBranchId = Schema::hasColumn('counters', 'branch_id');
        $hasAssignedUserId = Schema::hasColumn('counters', 'assigned_user_id');

        if (! $hasBranchId && ! $hasAssignedUserId) {
            return;
        }

        if ($hasBranchId) {
            try {
                Schema::table('counters', function (Blueprint $table) {
                    $table->dropIndex(['branch_id']);
                });
            } catch (\Throwable $e) {
            }
        }

        if ($hasAssignedUserId) {
            try {
                Schema::table('counters', function (Blueprint $table) {
                    $table->dropIndex(['assigned_user_id']);
                });
            } catch (\Throwable $e) {
            }
        }

        Schema::table('counters', function (Blueprint $table) use ($hasBranchId, $hasAssignedUserId) {
            if ($hasBranchId) {
                $table->dropColumn('branch_id');
            }

            if ($hasAssignedUserId) {
                $table->dropColumn('assigned_user_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('counters')) {
            return;
        }

        $hasBranchId = Schema::hasColumn('counters', 'branch_id');
        $hasAssignedUserId = Schema::hasColumn('counters', 'assigned_user_id');

        if ($hasBranchId && $hasAssignedUserId) {
            return;
        }

        Schema::table('counters', function (Blueprint $table) use ($hasBranchId, $hasAssignedUserId) {
            if (! $hasBranchId) {
                $table->unsignedBigInteger('branch_id')->default(1)->after('id');
                $table->index(['branch_id']);
            }

            if (! $hasAssignedUserId) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('counter_number');
                $table->index(['assigned_user_id']);
            }
        });
    }
};
