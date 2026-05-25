<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_level_library', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->integer('hierarchy')->default(0)->comment('Lower value = higher authority');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default access levels
        $now = now();
        $levels = [
            [
                'code' => 'ADMIN',
                'name' => 'Administrator',
                'description' => 'Full system access',
                'hierarchy' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'STAFF',
                'name' => 'Staff',
                'description' => 'Standard access (no libraries)',
                'hierarchy' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'GUARD',
                'name' => 'Guard',
                'description' => 'Entrance and queuing only',
                'hierarchy' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('access_level_library')->insert($levels);

        // Modify users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('access_level_id')->nullable()->after('email');
            $table->foreign('access_level_id')->references('id')->on('access_level_library');
        });

        // Migrate existing data from access_level ENUM to access_level_id
        if (Schema::hasColumn('users', 'access_level')) {
            $adminId = DB::table('access_level_library')->where('code', 'ADMIN')->value('id');
            $staffId = DB::table('access_level_library')->where('code', 'STAFF')->value('id');
            $guardId = DB::table('access_level_library')->where('code', 'GUARD')->value('id');

            if ($adminId) DB::table('users')->where('access_level', 'Administrator')->update(['access_level_id' => $adminId]);
            if ($staffId) DB::table('users')->where('access_level', 'Staff')->update(['access_level_id' => $staffId]);
            if ($guardId) DB::table('users')->where('access_level', 'Guard')->update(['access_level_id' => $guardId]);
            
            // Set default for any nulls (new users created during migration gap)
            if ($staffId) DB::table('users')->whereNull('access_level_id')->update(['access_level_id' => $staffId]);

            // Drop the old column
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('access_level');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Re-add the enum column
                $table->enum('access_level', ['Administrator', 'Staff', 'Guard'])->default('Staff')->after('email');
            });

            // Restore data
            $adminId = DB::table('access_level_library')->where('code', 'ADMIN')->value('id');
            $staffId = DB::table('access_level_library')->where('code', 'STAFF')->value('id');
            $guardId = DB::table('access_level_library')->where('code', 'GUARD')->value('id');

            if ($adminId) DB::table('users')->where('access_level_id', $adminId)->update(['access_level' => 'Administrator']);
            if ($staffId) DB::table('users')->where('access_level_id', $staffId)->update(['access_level' => 'Staff']);
            if ($guardId) DB::table('users')->where('access_level_id', $guardId)->update(['access_level' => 'Guard']);

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['access_level_id']);
                $table->dropColumn('access_level_id');
            });
        }

        Schema::dropIfExists('access_level_library');
    }
};
