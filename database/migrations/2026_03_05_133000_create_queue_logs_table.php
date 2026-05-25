<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queue_logs')) {
            Schema::create('queue_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
                $table->string('action', 50);
                $table->string('old_status', 30)->nullable();
                $table->string('new_status', 30)->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['queue_id', 'created_at']);
                $table->index(['action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_logs');
    }
};
