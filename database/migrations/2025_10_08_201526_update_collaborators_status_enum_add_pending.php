<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE collaborators DROP CONSTRAINT IF EXISTS collaborators_status_check");
            DB::statement("ALTER TABLE collaborators ADD CONSTRAINT collaborators_status_check CHECK (status IN ('active', 'pending', 'inactive'))");
            DB::statement("ALTER TABLE collaborators ALTER COLUMN status SET DEFAULT 'active'");
            return;
        }

        Schema::table('collaborators', function (Blueprint $table) {
            // Thay đổi enum status để thêm 'pending'
            $table->enum('status', ['active', 'pending', 'inactive'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE collaborators DROP CONSTRAINT IF EXISTS collaborators_status_check");
            DB::statement("ALTER TABLE collaborators ADD CONSTRAINT collaborators_status_check CHECK (status IN ('active', 'inactive'))");
            DB::statement("ALTER TABLE collaborators ALTER COLUMN status SET DEFAULT 'active'");
            return;
        }

        Schema::table('collaborators', function (Blueprint $table) {
            // Trở lại enum cũ
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }
};
