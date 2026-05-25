<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transfer_priority_rules')) {
            Schema::create('transfer_priority_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rule_key', 80)->unique();
                $table->foreignId('from_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->foreignId('to_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->integer('priority_score')->default(0);
                $table->integer('sequence')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('transfer_priority_rules')) {
            Schema::table('transfer_priority_rules', function (Blueprint $table) {
                $table->index(['is_active', 'from_transaction_id', 'to_transaction_id'], 'tpr_active_from_to_idx');
                $table->index(['priority_score', 'sequence'], 'tpr_score_seq_idx');
            });
        }

        if (Schema::hasTable('queues')) {
            Schema::table('queues', function (Blueprint $table) {
                if (! Schema::hasColumn('queues', 'is_transfer')) {
                    $table->boolean('is_transfer')->default(false)->index();
                }
                if (! Schema::hasColumn('queues', 'transfer_priority_rule_id')) {
                    $table->foreignId('transfer_priority_rule_id')
                        ->nullable()
                        ->constrained('transfer_priority_rules')
                        ->nullOnDelete()
                        ->index();
                }
                if (! Schema::hasColumn('queues', 'transfer_priority_score')) {
                    $table->integer('transfer_priority_score')->default(0)->index();
                }
                if (! Schema::hasColumn('queues', 'transfer_classified_at')) {
                    $table->timestamp('transfer_classified_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('queues')) {
            Schema::table('queues', function (Blueprint $table) {
                if (Schema::hasColumn('queues', 'transfer_priority_rule_id')) {
                    $table->dropConstrainedForeignId('transfer_priority_rule_id');
                }
                if (Schema::hasColumn('queues', 'is_transfer')) {
                    $table->dropColumn('is_transfer');
                }
                if (Schema::hasColumn('queues', 'transfer_priority_score')) {
                    $table->dropColumn('transfer_priority_score');
                }
                if (Schema::hasColumn('queues', 'transfer_classified_at')) {
                    $table->dropColumn('transfer_classified_at');
                }
            });
        }

        if (Schema::hasTable('transfer_priority_rules')) {
            Schema::table('transfer_priority_rules', function (Blueprint $table) {
                $table->dropIndex('tpr_active_from_to_idx');
                $table->dropIndex('tpr_score_seq_idx');
            });
        }

        Schema::dropIfExists('transfer_priority_rules');
    }
};
