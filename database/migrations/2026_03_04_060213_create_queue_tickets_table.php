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
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number'); // e.g. A-001
            $table->foreignId('service_id')->constrained()->onDelete('cascade');

            if (DB::getDriverName() === 'sqlite') {
                $table->unsignedBigInteger('counter_id')->nullable();
            } else {
                $table->foreignId('counter_id')->nullable()->constrained()->onDelete('set null');
            }

            $table->enum('status', ['waiting', 'serving', 'completed', 'skipped', 'cancelled'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Optional: track which user served the ticket
            $table->foreignId('served_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
