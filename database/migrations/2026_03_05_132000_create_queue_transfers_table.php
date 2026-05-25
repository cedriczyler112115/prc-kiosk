<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queue_transfers')) {
            Schema::create('queue_transfers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
                $table->unsignedBigInteger('from_transaction_id');
                $table->unsignedBigInteger('to_transaction_id');
                $table->foreignId('transferred_by')->constrained('users');
                $table->string('remarks', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['queue_id']);
                $table->index(['from_transaction_id']);
                $table->index(['to_transaction_id']);
                $table->index(['transferred_by']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_transfers');
    }
};
