<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('priorities')) {
            return;
        }

        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->integer('priority_level')->default(1);
            $table->string('color', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code']);
            $table->index(['priority_level']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
