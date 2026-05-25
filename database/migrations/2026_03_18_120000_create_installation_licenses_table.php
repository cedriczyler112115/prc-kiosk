<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_licenses', function (Blueprint $table) {
            $table->id();
            $table->longText('token');
            $table->unsignedBigInteger('installed_by')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->string('device_mac', 32)->nullable();
            $table->string('device_hash', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_licenses');
    }
};

