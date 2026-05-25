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
        if (! Schema::hasTable('queues')) {
            Schema::create('queues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
                $table->foreignId('priority_id')->nullable()->constrained('priorities')->onDelete('set null');

                if (DB::getDriverName() === 'sqlite') {
                    $table->unsignedBigInteger('counter_id')->nullable();
                } else {
                    $table->foreignId('counter_id')->nullable()->constrained('counters')->onDelete('set null');
                }

                $table->string('queue_number');
                $table->enum('status', ['waiting', 'called', 'serving', 'transferred', 'completed', 'skipped', 'cancelled', 'recalled'])->default('waiting');
                $table->timestamp('called_at')->nullable();
                $table->timestamp('serving_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('waiting_time_seconds')->nullable();
                $table->integer('service_time_seconds')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
