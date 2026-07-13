<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key if exists (not for sqlite)
            if (DB::getDriverName() !== 'sqlite') {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_NAME = 'users'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    AND CONSTRAINT_NAME LIKE '%counter_id%'
                ");
                
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            }
            
            // Change column to string type
            $table->string('counter_id', 50)->nullable()->change();
        });
        
        Schema::table('queues', function (Blueprint $table) {
            // Drop foreign key if exists (not for sqlite)
            if (DB::getDriverName() !== 'sqlite') {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_NAME = 'queues'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    AND CONSTRAINT_NAME LIKE '%counter_id%'
                ");
                
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            }
            
            // Change column to string type
            $table->string('counter_id', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('counter_id')->nullable()->change();
        });
        
        Schema::table('queues', function (Blueprint $table) {
            $table->unsignedBigInteger('counter_id')->nullable()->change();
        });
    }
};
