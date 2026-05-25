<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('counters')) {
            Schema::create('counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('transaction_id');
                $table->string('name', 100);
                $table->integer('counter_number');
                $table->unsignedBigInteger('assigned_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['branch_id']);
                $table->index(['transaction_id']);
                $table->index(['assigned_user_id']);
                $table->index(['is_active']);
            });

            return;
        }

        Schema::table('counters', function (Blueprint $table) {
            if (! Schema::hasColumn('counters', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->default(1)->after('id');
                $table->index(['branch_id']);
            }
            if (! Schema::hasColumn('counters', 'transaction_id')) {
                $table->unsignedBigInteger('transaction_id')->default(1)->after('branch_id');
                $table->index(['transaction_id']);
            }
            if (! Schema::hasColumn('counters', 'counter_number')) {
                $table->integer('counter_number')->default(1)->after('name');
            }
            if (! Schema::hasColumn('counters', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('counter_number');
                $table->index(['assigned_user_id']);
            }
            if (! Schema::hasColumn('counters', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            if (Schema::hasColumn('counters', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('counters', 'assigned_user_id')) {
                $table->dropIndex(['assigned_user_id']);
                $table->dropColumn('assigned_user_id');
            }
            if (Schema::hasColumn('counters', 'counter_number')) {
                $table->dropColumn('counter_number');
            }
            if (Schema::hasColumn('counters', 'transaction_id')) {
                $table->dropIndex(['transaction_id']);
                $table->dropColumn('transaction_id');
            }
            if (Schema::hasColumn('counters', 'branch_id')) {
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
