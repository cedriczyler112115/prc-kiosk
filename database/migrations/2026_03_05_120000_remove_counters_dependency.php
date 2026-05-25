<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if (Schema::hasTable('counters')) {
            if (! $isSqlite && Schema::hasTable('users') && Schema::hasColumn('users', 'counter_id') && Schema::hasColumn('counters', 'counter_number')) {
                DB::statement('UPDATE users u JOIN counters c ON u.counter_id = c.id SET u.counter_id = c.counter_number WHERE u.counter_id IS NOT NULL');
            }

            if (! $isSqlite && Schema::hasTable('queues') && Schema::hasColumn('queues', 'counter_id') && Schema::hasColumn('counters', 'counter_number')) {
                DB::statement('UPDATE queues q JOIN counters c ON q.counter_id = c.id SET q.counter_id = c.counter_number WHERE q.counter_id IS NOT NULL');
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'counter_id')) {
            if (! $isSqlite) {
                try {
                    Schema::table('users', function (Blueprint $table) {
                        $table->dropForeign(['counter_id']);
                    });
                } catch (\Throwable $e) {
                    // Fallback for manual FK names if needed
                    try {
                        DB::statement('ALTER TABLE users DROP FOREIGN KEY users_counter_id_foreign');
                    } catch (\Throwable $e2) {
                    }
                }
            }

            // For SQLite, we might need to recreate the table to change column type,
            // but since we are just changing integer type (maybe), let's see.
            // Actually, we are changing from foreign key to just integer.
            // Schema::table modify is supported in recent Laravel versions for some drivers.

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->integer('counter_id')->unsigned()->nullable()->change();
                });
            } catch (\Throwable $e) {
                // If change() fails (e.g. SQLite without doctrine/dbal), we might skip or use raw SQL if possible.
                // In SQLite, types are dynamic anyway.
                if (! $isSqlite) {
                    DB::statement('ALTER TABLE users MODIFY counter_id INT UNSIGNED NULL');
                }
            }

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('transaction_id');
                    $table->index('counter_id');
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('queues') && Schema::hasColumn('queues', 'counter_id')) {
            if (! $isSqlite) {
                try {
                    Schema::table('queues', function (Blueprint $table) {
                        $table->dropForeign(['counter_id']);
                    });
                } catch (\Throwable $e) {
                    try {
                        DB::statement('ALTER TABLE queues DROP FOREIGN KEY queues_counter_id_foreign');
                    } catch (\Throwable $e2) {
                    }
                }
            }

            try {
                Schema::table('queues', function (Blueprint $table) {
                    $table->integer('counter_id')->unsigned()->nullable()->change();
                });
            } catch (\Throwable $e) {
                if (! $isSqlite) {
                    DB::statement('ALTER TABLE queues MODIFY counter_id INT UNSIGNED NULL');
                }
            }

            try {
                Schema::table('queues', function (Blueprint $table) {
                    $table->index('counter_id');
                });
            } catch (\Throwable $e) {
            }
        }

        Schema::dropIfExists('counters');
    }

    public function down(): void
    {
        if (! Schema::hasTable('counters')) {
            Schema::create('counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->string('name', 100)->nullable();
                $table->integer('counter_number')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['transaction_id']);
                $table->index(['counter_number']);
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'transaction_id') && Schema::hasColumn('users', 'counter_id')) {
            DB::statement('INSERT INTO counters (transaction_id, name, counter_number, is_active, created_at, updated_at) SELECT DISTINCT u.transaction_id, CONCAT("Counter ", u.counter_id), u.counter_id, 1, NOW(), NOW() FROM users u WHERE u.transaction_id IS NOT NULL AND u.counter_id IS NOT NULL');

            DB::statement('UPDATE users u JOIN counters c ON u.transaction_id = c.transaction_id AND u.counter_id = c.counter_number SET u.counter_id = c.id WHERE u.counter_id IS NOT NULL');
        }

        if (Schema::hasTable('queues') && Schema::hasColumn('queues', 'transaction_id') && Schema::hasColumn('queues', 'counter_id')) {
            DB::statement('UPDATE queues q JOIN counters c ON q.transaction_id = c.transaction_id AND q.counter_id = c.counter_number SET q.counter_id = c.id WHERE q.counter_id IS NOT NULL');
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'counter_id')) {
            DB::statement('ALTER TABLE users MODIFY counter_id BIGINT UNSIGNED NULL');
            try {
                DB::statement('ALTER TABLE users ADD CONSTRAINT users_counter_id_foreign FOREIGN KEY (counter_id) REFERENCES counters(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('queues') && Schema::hasColumn('queues', 'counter_id')) {
            DB::statement('ALTER TABLE queues MODIFY counter_id BIGINT UNSIGNED NULL');
            try {
                DB::statement('ALTER TABLE queues ADD CONSTRAINT queues_counter_id_foreign FOREIGN KEY (counter_id) REFERENCES counters(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
            }
        }
    }
};
